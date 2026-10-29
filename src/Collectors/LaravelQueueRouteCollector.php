<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use Noldors\PHPStan\Laravel\QueueRouting;

/**
 * @phpstan-type QueueRoute array{kind: string, classes: list<string>, queues: list<string>, line: int}
 *
 * @implements Collector<Node\Expr\CallLike, QueueRoute>
 */
final class LaravelQueueRouteCollector implements Collector
{
    public function __construct(
        private readonly QueueRouting $routing,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        if (! $node instanceof Node\Expr\MethodCall && ! $node instanceof Node\Expr\StaticCall) {
            return null;
        }

        if (! $node->name instanceof Node\Identifier || $node->isFirstClassCallable()) {
            return null;
        }
        $method = \mb_strtolower($node->name->toString());

        if (! \in_array($method, ['route', 'set', 'forward'], true) || ! $this->routing->isQueueApi($node, $scope, $method)) {
            return null;
        }
        $argument = $this->routing->argument($node, 0, 'forward' === $method ? 'queue' : 'class');

        if (null === $argument) {
            return null;
        }
        $type = $scope->getType($argument);
        $targets = $this->strings($type);

        foreach ($type->getConstantArrays() as $array) {
            foreach ($array->getKeyTypes() as $key) {
                $targets = [...$targets, ...$this->strings($key)];
            }
        }

        return ['kind' => $method, 'classes' => 'forward' === $method ? [] : $targets, 'queues' => 'forward' === $method ? $targets : [], 'line' => $node->getStartLine()];
    }

    /**
     * @return list<string>
     */
    private function strings(Type|null $type): array
    {
        return \array_map(static fn ($string): string => $string->getValue(), $type?->getConstantStrings() ?? []);
    }
}
