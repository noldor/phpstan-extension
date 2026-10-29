<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use Noldors\PHPStan\Eloquent\Arguments;
use Noldors\PHPStan\Eloquent\ModelScope;
use Illuminate\Database\Migrations\Migration;

/**
 * @implements Collector<Node, array{alias: string, line: int}>
 */
final class EloquentMorphUsageCollector implements Collector
{
    /**
     * @param list<string> $morphTypeColumns
     */
    public function __construct(
        private readonly ModelScope $models,
        private readonly array $morphTypeColumns,
    ) {}

    public function getNodeType(): string
    {
        return Node::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        if (! $this->models->isEnabled() || $scope->getClassReflection()?->is(Migration::class)) {
            return null;
        }
        $value = $this->literalValue($node, $scope);

        return $value instanceof Node\Scalar\String_ ? ['alias' => $value->value, 'line' => $value->getStartLine()] : null;
    }

    private function literalValue(Node $node, Scope $scope): Node\Expr|null
    {
        if ($node instanceof Node\ArrayItem && $node->key instanceof Node\Scalar\String_ && $this->isDiscriminator($node->key->value)) {
            return $node->value;
        }

        if ($node instanceof Node\Expr\Assign && $node->var instanceof Node\Expr\PropertyFetch && $node->var->name instanceof Node\Identifier && $this->isDiscriminator($node->var->name->toString())) {
            return $node->expr;
        }

        if (! $node instanceof Node\Expr\MethodCall && ! $node instanceof Node\Expr\StaticCall) {
            return null;
        }

        if ($node->isFirstClassCallable() || ! $node->name instanceof Node\Identifier || ! \in_array(\mb_strtolower($node->name->toString()), ['where', 'orwhere'], true)) {
            return null;
        }
        $column = Arguments::string(Arguments::type($node->getArgs(), $scope, 'column', 0));

        if (null === $column || ! $this->isDiscriminator($column)) {
            return null;
        }
        $args = $node->getArgs();

        return Arguments::expression($args, 'value', 2)
            ?? (2 === \count($args) ? Arguments::expression($args, 'operator', 1) : null);
    }

    private function isDiscriminator(string $column): bool
    {
        $column = \mb_substr($column, (int) (\mb_strrpos($column, '.') === false ? 0 : \mb_strrpos($column, '.') + 1));

        return \in_array($column, $this->morphTypeColumns, true);
    }
}
