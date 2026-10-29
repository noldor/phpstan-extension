<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use Noldors\PHPStan\Eloquent\Arguments;
use Noldors\PHPStan\RestrictedUsage\ForbiddenApis;
use Noldors\PHPStan\Laravel\DependencyInjectionScope;

/**
 * @implements Rule<Node\Expr>
 */
final class LaravelServiceLocatorCallRule implements Rule
{
    private readonly ForbiddenApis $locators;

    /**
     * @param list<string> $serviceLocatorTypes
     * @param list<string> $serviceLocatorMethods
     */
    public function __construct(
        private readonly DependencyInjectionScope $selection,
        array $serviceLocatorTypes,
        array $serviceLocatorMethods,
    ) {
        $this->locators = new ForbiddenApis(\array_map(static fn (string $type): array => ['class' => $type, 'methods' => $serviceLocatorMethods], $serviceLocatorTypes), []);
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\StaticCall) || $node->isFirstClassCallable()) {
            return [];
        }

        return $this->callErrors($node, $scope);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function callErrors(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $call, Scope $scope): array
    {
        if (! $this->selection->contains($scope)) {
            return [];
        }
        $method = $call->name instanceof Node\Identifier ? $call->name->toString() : Arguments::string($scope->getType($call->name));

        if (null === $method) {
            return [];
        }

        if ($call instanceof Node\Expr\StaticCall) {
            $type = $call->class instanceof Node\Name ? $scope->resolveTypeByName($call->class) : $scope->getType($call->class);
        } else {
            $type = $scope->getType($call->var);
        }

        foreach ($type->getObjectTypeOrClassStringObjectType()->getObjectClassReflections() as $class) {
            if ($this->locators->matchesMethod($class, $method)) {
                return [RuleErrorBuilder::message('Inject dependencies instead of retrieving services through a service locator.')
                    ->identifier('architecture.dependencyInjection')->build()];
            }
        }

        return [];
    }
}
