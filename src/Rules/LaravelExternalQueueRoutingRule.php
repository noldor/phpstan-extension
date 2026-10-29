<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Laravel\QueueRouting;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @implements Rule<Node\Expr>
 */
final class LaravelExternalQueueRoutingRule implements Rule
{
    public function __construct(
        private readonly QueueRouting $routing,
        private readonly ReflectionProvider $reflection,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $violation = $this->propertyViolation($node, $scope) ?? $this->callViolation($node, $scope);

        return match ($violation) {
            'external' => $this->externalError(),
            'queue', 'connection', 'tries', 'timeout', 'backoff' => $this->attributeError($violation),
            default => [],
        };
    }

    private function propertyViolation(Node\Expr $node, Scope $scope): string|null
    {
        $property = $this->writtenProperty($node);

        if (null === $property || ! $this->routing->selected($scope->getType($property->var))) {
            return null;
        }

        $name = $this->propertyName($property, $scope);

        if (\in_array($name, ['queue', 'connection', 'chainQueue', 'chainConnection'], true) && ! $this->isOwnInstance($property->var, $scope)) {
            return 'external';
        }

        return $node instanceof Node\Expr\Assign && \in_array($name, ['queue', 'connection', 'tries', 'timeout', 'backoff'], true) && $this->staticConfiguration($node->expr, $scope, $name) ? $name : null;
    }

    private function callViolation(Node\Expr $node, Scope $scope): string|null
    {
        if (! $node instanceof Node\Expr\MethodCall && ! $node instanceof Node\Expr\StaticCall) {
            return null;
        }
        $name = $this->methodName($node, $scope);

        if (null === $name) {
            return null;
        }

        if ($node instanceof Node\Expr\MethodCall && \in_array($name, ['onqueue', 'onconnection', 'allonqueue', 'allonconnection'], true) && $this->routedReceiver($node->var, $scope, $name)) {
            return $this->routingCallViolation($node, $scope, $name);
        }

        return $this->routing->isQueueApi($node, $scope, $name) && $this->queueApiViolation($node, $scope, $name) ? 'external' : null;
    }

    private function methodName(Node\Expr\MethodCall|Node\Expr\StaticCall $call, Scope $scope): string|null
    {
        if ($call->name instanceof Node\Identifier) {
            return \mb_strtolower($call->name->toString());
        }
        $names = $scope->getType($call->name)->getConstantStrings();

        return 1 === \count($names) ? \mb_strtolower($names[0]->getValue()) : null;
    }

    private function routingCallViolation(Node\Expr\MethodCall $call, Scope $scope, string $name): string|null
    {
        if (! $this->isOwnInstance($call->var, $scope)) {
            return 'external';
        }
        $property = \str_contains($name, 'connection') ? 'connection' : 'queue';
        $value = $this->routing->argument($call, 0, $property);

        return null !== $value && $this->staticConfiguration($value, $scope, $property) ? $property : null;
    }

    private function staticConfiguration(Node\Expr $value, Scope $scope, string $property): bool
    {
        return $this->routing->isConstant($value) && ! $scope->getType($value)->isNull()->yes() && $this->routing->supportsAttribute($property);
    }

    private function queueApiViolation(Node\Expr\MethodCall|Node\Expr\StaticCall $call, Scope $scope, string $name): bool
    {
        $positions = match ($name) {
            'pushon' => [1, 0], 'lateron' => [2, 0], 'push', 'bulk' => [0, 2], 'later' => [1, 3],
            default => null,
        };

        if (null === $positions) {
            return false;
        }
        [$jobPosition, $queuePosition] = $positions;
        $job = $this->routing->argument($call, $jobPosition, 'bulk' === $name ? 'jobs' : 'job');
        $queue = $this->routing->argument($call, $queuePosition, 'queue');

        $explicitQueue = null !== $queue && ! $scope->getType($queue)->isNull()->yes();

        return null !== $job && ($explicitQueue || $this->externalConnection($call, $scope)) && $this->containsSelected($job, $scope);
    }

    private function externalConnection(Node\Expr\MethodCall|Node\Expr\StaticCall $call, Scope $scope): bool
    {
        if (! $call instanceof Node\Expr\MethodCall || (! $call->var instanceof Node\Expr\MethodCall && ! $call->var instanceof Node\Expr\StaticCall)) {
            return false;
        }
        $connection = $call->var;

        if (! $connection->name instanceof Node\Identifier || 0 !== \strcasecmp($connection->name->toString(), 'connection') || ! $this->routing->isQueueApi($connection, $scope, 'connection')) {
            return false;
        }
        $name = $this->routing->argument($connection, 0, 'name');

        return null !== $name && ! $scope->getType($name)->isNull()->yes();
    }

    private function writtenProperty(Node\Expr $node): Node\Expr\PropertyFetch|null
    {
        $target = match (true) {
            $node instanceof Node\Expr\Assign, $node instanceof Node\Expr\AssignRef, $node instanceof Node\Expr\AssignOp => $node->var,
            $node instanceof Node\Expr\PreInc, $node instanceof Node\Expr\PostInc, $node instanceof Node\Expr\PreDec, $node instanceof Node\Expr\PostDec => $node->var,
            default => null,
        };

        if ($node instanceof Node\Expr\AssignRef && ! $target instanceof Node\Expr\PropertyFetch) {
            $target = $node->expr;
        }

        while ($target instanceof Node\Expr\ArrayDimFetch) {
            $target = $target->var;
        }

        return $target instanceof Node\Expr\PropertyFetch ? $target : null;
    }

    private function propertyName(Node\Expr\PropertyFetch $property, Scope $scope): string|null
    {
        if ($property->name instanceof Node\Identifier) {
            return $property->name->toString();
        }
        $names = $scope->getType($property->name)->getConstantStrings();

        return 1 === \count($names) ? $names[0]->getValue() : null;
    }

    private function isOwnInstance(Node\Expr $receiver, Scope $scope): bool
    {
        return $scope->getType($receiver) instanceof \PHPStan\Type\ThisType && null !== $scope->getClassReflection() && null !== $this->routing->role($scope->getClassReflection());
    }

    private function routedReceiver(Node\Expr $receiver, Scope $scope, string $method): bool
    {
        foreach ($scope->getType($receiver)->getObjectClassReflections() as $class) {
            if (null !== $this->routing->role($class) && $this->routing->nativeMethod($class, \Illuminate\Bus\Queueable::class, $method)) {
                return true;
            }

            foreach ([\Illuminate\Foundation\Bus\PendingDispatch::class, \Illuminate\Foundation\Bus\PendingChain::class, \Illuminate\Bus\PendingBatch::class] as $owner) {
                if ($class->is($owner) && $this->routing->nativeMethod($class, $owner, $method) && $this->pendingSelected($receiver, $scope)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function pendingSelected(Node\Expr $receiver, Scope $scope): bool
    {
        if ($receiver instanceof Node\Expr\FuncCall && $receiver->name instanceof Node\Name && $this->reflection->hasFunction($receiver->name, $scope) && 'dispatch' === $this->reflection->getFunction($receiver->name, $scope)->getName()) {
            $job = $this->routing->argument($receiver, 0, 'job');

            return null !== $job && $this->containsSelected($job, $scope);
        }

        if ($receiver instanceof Node\Expr\StaticCall && $receiver->class instanceof Node\Name && $receiver->name instanceof Node\Identifier) {
            return $this->staticPendingSelected($receiver, $scope);
        }

        if ($receiver instanceof Node\Expr\New_ && $receiver->class instanceof Node\Name && \in_array($scope->resolveName($receiver->class), [\Illuminate\Foundation\Bus\PendingChain::class, \Illuminate\Bus\PendingBatch::class], true)) {
            return $this->constructedPendingSelected($receiver, $scope);
        }

        return $receiver instanceof Node\Expr\MethodCall && $receiver->name instanceof Node\Identifier && $this->methodPendingSelected($receiver, $scope);
    }

    private function constructedPendingSelected(Node\Expr\New_ $receiver, Scope $scope): bool
    {
        foreach ($receiver->getArgs() as $argument) {
            if ($this->containsSelected($argument->value, $scope)) {
                return true;
            }
        }

        return false;
    }

    private function staticPendingSelected(Node\Expr\StaticCall $call, Scope $scope): bool
    {
        if (! $call->class instanceof Node\Name || ! $call->name instanceof Node\Identifier) {
            return false;
        }
        $name = \mb_strtolower($call->name->toString());
        $className = $scope->resolveName($call->class);

        if (! $this->reflection->hasClass($className)) {
            return false;
        }
        $class = $this->reflection->getClass($className);

        if (\in_array($name, ['dispatch', 'dispatchif', 'dispatchunless'], true)) {
            return null !== $this->routing->role($class) && $this->routing->nativeMethod($class, \Illuminate\Foundation\Bus\Dispatchable::class, $name) && $this->routing->nativeMethod($class, \Illuminate\Foundation\Bus\Dispatchable::class, 'newPendingDispatch');
        }
        $jobs = $this->routing->argument($call, 0, 'jobs');

        return $class->is(\Illuminate\Support\Facades\Bus::class) && \in_array($name, ['chain', 'batch'], true) && ! $class->hasNativeMethod($name) && null !== $jobs && $this->containsSelected($jobs, $scope);
    }

    private function methodPendingSelected(Node\Expr\MethodCall $call, Scope $scope): bool
    {
        if (! $call->name instanceof Node\Identifier) {
            return false;
        }
        $method = \mb_strtolower($call->name->toString());

        foreach ($scope->getType($call->var)->getObjectClassReflections() as $class) {
            if ($class->is(\Illuminate\Bus\Dispatcher::class) && \in_array($method, ['chain', 'batch'], true) && $this->routing->nativeMethod($class, \Illuminate\Bus\Dispatcher::class, $method)) {
                $jobs = $this->routing->argument($call, 0, 'jobs');

                return null !== $jobs && $this->containsSelected($jobs, $scope);
            }

            if ($this->nativePendingMethod($class, $method) && ($this->addedSelected($call, $scope, $method) || $this->pendingSelected($call->var, $scope))) {
                return true;
            }
        }

        return false;
    }

    private function addedSelected(Node\Expr\MethodCall $call, Scope $scope, string $method): bool
    {
        if (! \in_array($method, ['add', 'prepend', 'append', 'chain'], true)) {
            return false;
        }
        $argument = $call->getArgs()[0] ?? null;

        return null !== $argument && $this->containsSelected($argument->value, $scope);
    }

    private function nativePendingMethod(ClassReflection $class, string $method): bool
    {
        foreach ([\Illuminate\Foundation\Bus\PendingDispatch::class, \Illuminate\Foundation\Bus\PendingChain::class, \Illuminate\Bus\PendingBatch::class] as $owner) {
            if ($class->is($owner) && $this->routing->nativeMethod($class, $owner, $method)) {
                return true;
            }
        }

        return false;
    }

    private function containsSelected(Node\Expr $expression, Scope $scope, bool $keys = false): bool
    {
        return $this->selectedType($scope->getType($expression), $keys);
    }

    private function selectedType(\PHPStan\Type\Type $type, bool $keys = false): bool
    {
        if ($this->routing->selected($type)) {
            return true;
        }

        foreach ($type->getConstantArrays() as $array) {
            foreach ($keys ? $array->getKeyTypes() : $array->getValueTypes() as $value) {
                if ($this->selectedType($value)) {
                    return true;
                }
            }
        }

        return ! $keys && $type->isArray()->yes() && $this->routing->selected($type->getIterableValueType());
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function externalError(): array
    {
        return [RuleErrorBuilder::message('Declare queue routing inside the job, listener or notification; do not assign routes from outside.')->identifier('laravel.externalQueueRoute')->build()];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function attributeError(string $property): array
    {
        return [RuleErrorBuilder::message('Declare static ' . $property . ' configuration with the native queue attribute; keep runtime decisions inside the producer.')->identifier('laravel.queueAttribute')->build()];
    }
}
