<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodCallableNode;

/**
 * @implements Rule<MethodCallableNode>
 */
final class LaravelQueueOverrideCallableRule implements Rule
{
    public function __construct(
        private readonly LaravelQueueOverrideRule $calls,
    ) {}

    public function getNodeType(): string
    {
        return MethodCallableNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        return $this->calls->processNode($node->getOriginalNode(), $scope);
    }
}
