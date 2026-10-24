<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Node\InstantiationCallableNode;

/**
 * PHPStan dispatches first-class callables through their own virtual node types.
 *
 * @implements Rule<FunctionCallableNode|StaticMethodCallableNode|MethodCallableNode|InstantiationCallableNode>
 */
final class LaravelValidationCallableRule implements Rule
{
    /**
     * @param class-string<FunctionCallableNode|StaticMethodCallableNode|MethodCallableNode|InstantiationCallableNode> $nodeType
     */
    public function __construct(
        private readonly LaravelValidationLocationRule $validationRule,
        private readonly string $nodeType,
    ) {}

    public function getNodeType(): string
    {
        return $this->nodeType;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        return $this->validationRule->processNode($node->getOriginalNode(), $scope);
    }
}
