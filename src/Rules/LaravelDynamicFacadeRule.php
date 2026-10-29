<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use Illuminate\Support\Facades\Facade;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\Laravel\DependencyInjectionScope;

/**
 * Restricted class-name extensions cover ordinary named calls; PHPStan does not
 * invoke them for every dynamic method name or class-string expression.
 *
 * @implements Rule<Node\Expr\StaticCall>
 */
final class LaravelDynamicFacadeRule implements Rule
{
    public function __construct(
        private readonly DependencyInjectionScope $selection,
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr\StaticCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        return $node->isFirstClassCallable() ? [] : $this->callErrors($node, $scope);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function callErrors(Node\Expr\StaticCall $call, Scope $scope): array
    {
        if (! $this->selection->contains($scope) || $call->class instanceof Node\Name && $call->name instanceof Node\Identifier) {
            return [];
        }

        $classes = $call->class instanceof Node\Name ? [$scope->resolveName($call->class)] : $scope->getType($call->class)->getObjectTypeOrClassStringObjectType()->getObjectClassNames();

        foreach ($classes as $class) {
            if ($this->reflectionProvider->hasClass($class) && $this->reflectionProvider->getClass($class)->is(Facade::class)) {
                return [RuleErrorBuilder::message('Inject dependencies instead of invoking a Laravel facade through a dynamic method or class name.')
                    ->identifier('architecture.dependencyInjection')->build()];
            }
        }

        return [];
    }
}
