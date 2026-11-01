<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Collectors\Collector;
use Noldors\PHPStan\Laravel\RetainedResolverPolicy;

/**
 * @phpstan-type RetainedRoot array{name: string, line: int}
 *
 * @implements Collector<InClassNode, RetainedRoot>
 */
final class RetainedResolverCollector implements Collector
{
    public function __construct(
        private readonly RetainedResolverPolicy $policy,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        $class = $node->getClassReflection();

        if ($class->isAbstract() || $class->isInterface() || $class->isTrait() || ! $this->policy->roots->contains($class)) {
            return null;
        }

        return ['name' => $class->getName(), 'line' => $node->getStartLine()];
    }
}
