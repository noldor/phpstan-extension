<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Collectors\Collector;

/**
 * @implements Collector<InClassNode, array{class: string, missingEnumLine: int|null, missingCases: array<string, int>}>
 */
final class GraphQLEnumDescriptionCollector implements Collector
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        $enum = $node->getOriginalNode();

        if (! $enum instanceof Node\Stmt\Enum_ || null === $enum->name) {
            return null;
        }
        $missingEnumLine = $this->hasDescription($enum->attrGroups, $scope) ? null : $enum->name->getStartLine();
        $missingCases = [];

        foreach ($enum->stmts as $statement) {
            if ($statement instanceof Node\Stmt\EnumCase && ! $this->hasDescription($statement->attrGroups, $scope)) {
                $missingCases[$statement->name->toString()] = $statement->name->getStartLine();
            }
        }

        if (null === $missingEnumLine && [] === $missingCases) {
            return null;
        }

        return ['class' => $node->getClassReflection()->getName(), 'missingEnumLine' => $missingEnumLine, 'missingCases' => $missingCases];
    }

    /**
     * @param array<Node\AttributeGroup> $groups
     */
    private function hasDescription(array $groups, Scope $scope): bool
    {
        foreach ($groups as $group) {
            foreach ($group->attrs as $attribute) {
                if (0 === \strcasecmp($scope->resolveName($attribute->name), 'GraphQL\\Type\\Definition\\Description')) {
                    return true;
                }
            }
        }

        return false;
    }
}
