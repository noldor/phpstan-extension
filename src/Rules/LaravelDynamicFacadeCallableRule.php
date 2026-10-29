<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\StaticMethodCallableNode;

/**
 * @implements Rule<StaticMethodCallableNode>
 */
final class LaravelDynamicFacadeCallableRule implements Rule
{
    public function __construct(
        private readonly LaravelDynamicFacadeRule $facadeRule,
    ) {}

    public function getNodeType(): string
    {
        return StaticMethodCallableNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        return $this->facadeRule->callErrors($node->getOriginalNode(), $scope);
    }
}
