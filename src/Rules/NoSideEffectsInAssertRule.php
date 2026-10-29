<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\RestrictedUsage\ForbiddenApis;
use Noldors\PHPStan\Assertions\AssertArgumentVisitor;

/**
 * @implements Rule<Node\Expr>
 */
final class NoSideEffectsInAssertRule implements Rule
{
    private readonly ForbiddenApis $apis;

    /**
     * @param list<array{class: string, methods: list<string>}> $forbiddenMethods
     * @param list<string> $forbiddenFunctions
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        array $forbiddenMethods = [],
        array $forbiddenFunctions = [],
    ) {
        $this->apis = new ForbiddenApis($forbiddenMethods, $forbiddenFunctions);
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->isInsideAssert($node, $scope) || ! $this->hasSideEffect($node, $scope)) {
            return [];
        }

        return [RuleErrorBuilder::message('Move side effects out of assert(); its arguments may not execute in production.')
            ->identifier('assert.sideEffect')->build()];
    }

    private function isInsideAssert(Node\Expr $node, Scope $scope): bool
    {
        /** @var list<Node\Name> $names */
        $names = $node->getAttribute(AssertArgumentVisitor::ATTRIBUTE, []);

        foreach ($names as $name) {
            if ($this->functionName($name, $scope) === 'assert') {
                return true;
            }
        }

        return false;
    }

    private function hasSideEffect(Node\Expr $node, Scope $scope): bool
    {
        if ($this->isAssignment($node)) {
            return true;
        }

        if ($node instanceof Node\Expr\FuncCall) {
            if ($node->isFirstClassCallable()) {
                return false;
            }

            $name = $this->functionName($node->name, $scope);

            return null !== $name && $this->apis->matchesFunction($name);
        }

        if ($node instanceof Node\Expr\New_) {
            $method = $scope->getMethodReflection($scope->getType($node), '__construct');

            return null !== $method && $this->apis->matchesMethod($method->getDeclaringClass(), $method->getName());
        }

        if (! $node instanceof Node\Expr\MethodCall && ! $node instanceof Node\Expr\NullsafeMethodCall && ! $node instanceof Node\Expr\StaticCall) {
            return false;
        }

        return $this->methodHasSideEffect($node, $scope);
    }

    private function isAssignment(Node\Expr $node): bool
    {
        return $node instanceof Node\Expr\Assign || $node instanceof Node\Expr\AssignRef || $node instanceof Node\Expr\AssignOp
            || $node instanceof Node\Expr\PreInc || $node instanceof Node\Expr\PostInc
            || $node instanceof Node\Expr\PreDec || $node instanceof Node\Expr\PostDec;
    }

    private function methodHasSideEffect(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $node, Scope $scope): bool
    {
        if ($node->isFirstClassCallable()) {
            return false;
        }

        $name = $node->name instanceof Node\Identifier ? $node->name->toString() : $this->constantString($node->name, $scope);

        if (null === $name) {
            return false;
        }

        if ($node instanceof Node\Expr\StaticCall) {
            $receiver = $node->class instanceof Node\Name
                ? $scope->resolveTypeByName($node->class)
                : $scope->getType($node->class)->getClassStringObjectType();
        } else {
            $receiver = $scope->getType($node->var);
        }

        $method = $scope->getMethodReflection($receiver, $name);

        return null !== $method && $this->apis->matchesMethod($method->getDeclaringClass(), $method->getName());
    }

    private function functionName(Node\Expr|Node\Name $name, Scope $scope): string|null
    {
        if (! $name instanceof Node\Name) {
            $constant = $this->constantString($name, $scope);

            if (null === $constant || '' === \mb_ltrim($constant, '\\') || \str_contains($constant, '::')) {
                return null;
            }

            $name = new Node\Name\FullyQualified(\mb_ltrim($constant, '\\'));
        }

        if (! $this->reflectionProvider->hasFunction($name, $scope)) {
            return null;
        }

        return \mb_strtolower($this->reflectionProvider->getFunction($name, $scope)->getName());
    }

    private function constantString(Node\Expr $node, Scope $scope): string|null
    {
        $type = $scope->getType($node);
        $strings = $type->getConstantStrings();

        if (\count($strings) !== 1 || ! $strings[0]->isSuperTypeOf($type)->yes()) {
            return null;
        }

        return $strings[0]->getValue();
    }
}
