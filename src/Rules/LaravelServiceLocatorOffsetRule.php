<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Laravel\DependencyInjectionScope;

/**
 * @implements Rule<Node\Expr\ArrayDimFetch>
 */
final class LaravelServiceLocatorOffsetRule implements Rule
{
    /**
     * @param list<string> $serviceLocatorTypes
     */
    public function __construct(
        private readonly DependencyInjectionScope $selection,
        private readonly array $serviceLocatorTypes,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr\ArrayDimFetch::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->selection->contains($scope) || $scope->isInExpressionAssign($node)) {
            return [];
        }

        foreach ($scope->getType($node->var)->getObjectClassReflections() as $class) {
            foreach ($this->serviceLocatorTypes as $type) {
                if ($class->is(\mb_ltrim($type, '\\'))) {
                    return [RuleErrorBuilder::message('Inject dependencies instead of retrieving services through container array access.')
                        ->identifier('architecture.dependencyInjection')->build()];
                }
            }
        }

        return [];
    }
}
