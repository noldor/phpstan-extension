<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\StaticMethodCallableNode;

/**
 * @implements Rule<MethodCallableNode|StaticMethodCallableNode>
 */
final class LaravelUseCaseActorCallableRule implements Rule
{
    /**
     * @param class-string<MethodCallableNode|StaticMethodCallableNode> $nodeType
     */
    public function __construct(
        private readonly LaravelUseCaseActorRule $actorRule,
        private readonly string $nodeType,
    ) {}

    public function getNodeType(): string
    {
        return $this->nodeType;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        return $this->actorRule->callErrors($node->getOriginalNode(), $scope);
    }
}
