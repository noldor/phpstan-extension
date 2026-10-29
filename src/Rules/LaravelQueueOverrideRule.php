<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Laravel\QueuePolicy;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @implements Rule<Node\Expr>
 */
final class LaravelQueueOverrideRule implements Rule
{
    public function __construct(
        private readonly QueuePolicy $policy,
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $violation = $this->assignmentViolation($node, $scope) ?? $this->callViolation($node, $scope);

        if (null === $violation) {
            return [];
        }
        [$identifier, $message] = $violation;

        return [RuleErrorBuilder::message($message)->identifier($identifier)->build()];
    }

    /**
     * @return array{string, string}|null
     */
    private function assignmentViolation(Node\Expr $node, Scope $scope): array|null
    {
        if (! $node instanceof Node\Expr\Assign || ! $node->var instanceof Node\Expr\PropertyFetch || ! $node->var->name instanceof Node\Identifier || $node->var->name->toString() !== 'afterCommit') {
            return null;
        }
        $class = $this->policy->classOf($scope->getType($node->var->var));

        if (null === $class || ! $this->policy->hasPublicFlag($class)) {
            return null;
        }

        if ($this->policy->isJob($class) && $scope->getType($node->expr)->isFalse()->yes()) {
            return $this->jobViolation();
        }

        if ($this->policy->isObserver($class) && $scope->getType($node->expr)->toBoolean()->isTrue()->yes()) {
            return $this->observerViolation();
        }

        return null;
    }

    /**
     * @return array{string, string}|null
     */
    private function callViolation(Node\Expr $node, Scope $scope): array|null
    {
        if (! $node instanceof Node\Expr\MethodCall || ! $node->name instanceof Node\Identifier) {
            return null;
        }
        $name = \mb_strtolower($node->name->toString());

        if ('beforecommit' === $name && null !== ($job = $this->dispatchedJob($node->var, $scope)) && $this->hasNativeImplementation($job, \Illuminate\Bus\Queueable::class, 'beforeCommit')) {
            return $this->jobViolation();
        }
        $class = $this->policy->classOf($scope->getType($node->var));

        if ('aftercommit' === $name && null !== $class && $this->policy->isObserver($class) && $this->hasNativeImplementation($class, \Illuminate\Bus\Queueable::class, 'afterCommit')) {
            return $this->observerViolation();
        }

        return null;
    }

    private function dispatchedJob(Node\Expr $receiver, Scope $scope): ClassReflection|null
    {
        $class = $this->policy->classOf($scope->getType($receiver));

        if (null !== $class && $this->policy->isJob($class)) {
            return $class;
        }

        if ($receiver instanceof Node\Expr\StaticCall) {
            return $this->staticDispatch($receiver, $scope);
        }

        if ($receiver instanceof Node\Expr\FuncCall && $receiver->name instanceof Node\Name && $this->reflectionProvider->hasFunction($receiver->name, $scope) && $this->reflectionProvider->getFunction($receiver->name, $scope)->getName() === 'dispatch') {
            $argument = $receiver->getArgs()[0] ?? null;
            $job = null !== $argument ? $this->policy->classOf($scope->getType($argument->value)) : null;

            return null !== $job && $this->policy->isJob($job) ? $job : null;
        }

        if ($receiver instanceof Node\Expr\MethodCall && $this->isNativePendingFluent($receiver, $scope)) {
            return $this->dispatchedJob($receiver->var, $scope);
        }

        return null;
    }

    private function isNativePendingFluent(Node\Expr\MethodCall $call, Scope $scope): bool
    {
        if (! $call->name instanceof Node\Identifier || ! \in_array(\mb_strtolower($call->name->toString()), ['onconnection', 'onqueue', 'ongroup', 'withdeduplicator', 'allonconnection', 'allonqueue', 'delay', 'withoutdelay', 'aftercommit', 'beforecommit', 'chain', 'afterresponse'], true)) {
            return false;
        }
        $class = $this->policy->classOf($scope->getType($call->var));

        return null !== $class && $class->is(\Illuminate\Foundation\Bus\PendingDispatch::class) && $this->hasNativeImplementation($class, \Illuminate\Foundation\Bus\PendingDispatch::class, $call->name->toString());
    }

    private function staticDispatch(Node\Expr\StaticCall $call, Scope $scope): ClassReflection|null
    {
        if (! $call->class instanceof Node\Name || ! $call->name instanceof Node\Identifier || ! \in_array(\mb_strtolower($call->name->toString()), ['dispatch', 'dispatchif', 'dispatchunless'], true)) {
            return null;
        }
        $name = $scope->resolveName($call->class);

        if (! $this->reflectionProvider->hasClass($name)) {
            return null;
        }
        $class = $this->reflectionProvider->getClass($name);

        return $this->policy->isJob($class) && $this->hasNativeImplementation($class, \Illuminate\Foundation\Bus\Dispatchable::class, $call->name->toString()) && $this->hasNativeImplementation($class, \Illuminate\Foundation\Bus\Dispatchable::class, 'newPendingDispatch') ? $class : null;
    }

    private function hasNativeImplementation(ClassReflection $class, string $owner, string $name): bool
    {
        if (! $class->hasNativeMethod($name) || ! $this->reflectionProvider->hasClass($owner) || ! $this->reflectionProvider->getClass($owner)->hasNativeMethod($name)) {
            return false;
        }
        $method = $class->getNativeReflection()->getMethod($name);
        $native = $this->reflectionProvider->getClass($owner)->getNativeReflection()->getMethod($name);

        return $method->getFileName() === $native->getFileName() && $method->getStartLine() === $native->getStartLine();
    }

    /**
     * @return array{string, string}
     */
    private function jobViolation(): array
    {
        return ['laravel.queueBeforeCommit', 'Do not disable the after-commit guarantee on an application job.'];
    }

    /**
     * @return array{string, string}
     */
    private function observerViolation(): array
    {
        return ['laravel.observerAfterCommit', 'Keep model observers immediate: do not implement deferred/queued handler contracts or enable afterCommit.'];
    }
}
