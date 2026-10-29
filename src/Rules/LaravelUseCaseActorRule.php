<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use Illuminate\Support\Facades\Auth;
use Noldors\PHPStan\Eloquent\Arguments;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\Configuration\ClassScope;

/**
 * @implements Rule<Node\Expr>
 */
final class LaravelUseCaseActorRule implements Rule
{
    /**
     * @param list<string> $globalActorMethods
     */
    public function __construct(
        private readonly ClassScope $useCases,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly array $globalActorMethods,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->useCases->contains($scope->getClassReflection())
            || ! ($node instanceof Node\Expr\StaticCall || $node instanceof Node\Expr\MethodCall)) {
            return [];
        }

        if ($node->isFirstClassCallable()) {
            return [];
        }

        return $this->callErrors($node, $scope);
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    public function callErrors(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $call, Scope $scope): array
    {
        if (! $this->useCases->contains($scope->getClassReflection())) {
            return [];
        }

        $method = $call->name instanceof Node\Identifier ? $call->name->toString() : Arguments::string($scope->getType($call->name));

        if (null === $method || ! \in_array(\mb_strtolower($method), \array_map(\mb_strtolower(...), $this->globalActorMethods), true)) {
            return [];
        }

        $global = $call instanceof Node\Expr\StaticCall ? $this->isAuthFacade($call, $scope) : $this->isGlobalAuth($call->var, $scope);

        return $global ? [RuleErrorBuilder::message('Pass the actor or an explicit guard into the use case instead of looking up the global actor.')
            ->identifier('architecture.useCaseActor')->build()] : [];
    }

    private function isGlobalAuth(Node\Expr $expression, Scope $scope): bool
    {
        if ($expression instanceof Node\Expr\FuncCall && $expression->name instanceof Node\Name && $this->reflectionProvider->hasFunction($expression->name, $scope)) {
            return 'auth' === \mb_strtolower($this->reflectionProvider->getFunction($expression->name, $scope)->getName());
        }

        if ($expression instanceof Node\Expr\StaticCall) {
            return $this->isAuthFacade($expression, $scope);
        }

        return ($expression instanceof Node\Expr\MethodCall || $expression instanceof Node\Expr\NullsafeMethodCall) && $this->isGlobalAuth($expression->var, $scope);
    }

    private function isAuthFacade(Node\Expr\StaticCall $call, Scope $scope): bool
    {
        $classes = $call->class instanceof Node\Name ? [$scope->resolveName($call->class)] : $scope->getType($call->class)->getObjectTypeOrClassStringObjectType()->getObjectClassNames();

        foreach ($classes as $class) {
            if ($this->reflectionProvider->hasClass($class) && $this->reflectionProvider->getClass($class)->is(Auth::class)) {
                return true;
            }
        }

        return false;
    }
}
