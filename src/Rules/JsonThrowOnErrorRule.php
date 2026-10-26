<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Type\TypeCombinator;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantIntegerType;

/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class JsonThrowOnErrorRule implements Rule
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isFirstClassCallable()) {
            return [];
        }

        $function = $this->resolveFunction($node, $scope);

        if ('json_encode' !== $function && 'json_decode' !== $function) {
            return [];
        }

        $flags = $this->flagsArgument($node, 'json_encode' === $function ? 1 : 3);

        if (null !== $flags && $this->containsThrowFlag($flags, $scope)) {
            return [];
        }

        return [RuleErrorBuilder::message(\sprintf(
            'Pass JSON_THROW_ON_ERROR in the flags argument of %s(); preserve other required flags.',
            $function,
        ))->identifier('json.missingThrowOnError')->build()];
    }

    private function resolveFunction(Node\Expr\FuncCall $call, Scope $scope): string|null
    {
        if ($call->name instanceof Node\Name) {
            if (! $this->reflectionProvider->hasFunction($call->name, $scope)) {
                return null;
            }

            return \mb_strtolower($this->reflectionProvider->getFunction($call->name, $scope)->getName());
        }

        $type = $scope->getType($call->name);
        $names = $type->getConstantStrings();

        if (\count($names) !== 1 || ! $names[0]->isSuperTypeOf($type)->yes()) {
            return null;
        }

        return \mb_strtolower(\mb_ltrim($names[0]->getValue(), '\\'));
    }

    private function flagsArgument(Node\Expr\FuncCall $call, int $flagsPosition): Node\Expr|null
    {
        foreach ($call->getArgs() as $argument) {
            if ($argument->name?->toString() === 'flags') {
                return $argument->value;
            }
        }

        foreach ($call->getArgs() as $index => $argument) {
            if ($argument->unpack) {
                return null;
            }

            if ($index === $flagsPosition && null === $argument->name) {
                return $argument->value;
            }
        }

        return null;
    }

    private function containsThrowFlag(Node\Expr $expression, Scope $scope): bool
    {
        if ($expression instanceof Node\Expr\BinaryOp\BitwiseOr) {
            return $this->containsThrowFlag($expression->left, $scope) || $this->containsThrowFlag($expression->right, $scope);
        }

        $type = $scope->getType($expression);
        $values = $type->getConstantScalarTypes();

        if ([] === $values || ! TypeCombinator::union(...$values)->isSuperTypeOf($type)->yes()) {
            return false;
        }

        foreach ($values as $value) {
            if (! $value instanceof ConstantIntegerType || \JSON_THROW_ON_ERROR !== ($value->getValue() & \JSON_THROW_ON_ERROR)) {
                return false;
            }
        }

        return true;
    }
}
