<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\ClassPropertyNode;
use Noldors\PHPStan\Laravel\RetainedBindingFacts;
use Noldors\PHPStan\Laravel\RetainedResolverPolicy;

/**
 * @phpstan-import-type BindingFact from RetainedBindingFacts
 *
 * @implements Collector<ClassPropertyNode, list<BindingFact>>
 */
final class RetainedProviderBindingCollector implements Collector
{
    public function __construct(
        private readonly RetainedResolverPolicy $policy,
        private readonly RetainedBindingFacts $facts,
    ) {}

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        if (! $this->policy->collectsBindings($scope->getFile()) || ! \in_array($node->getName(), ['bindings', 'singletons'], true) || ! $node->getClassReflection()->is(\Illuminate\Support\ServiceProvider::class) || null === $node->getDefault()) {
            return null;
        }

        return $this->facts->map($scope->getType($node->getDefault()), $node->getStartLine(), 'singletons' === $node->getName());
    }
}
