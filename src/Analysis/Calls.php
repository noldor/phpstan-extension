<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Analysis;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Reflection\ReflectionProvider;

final class Calls
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function functionName(Node\Expr\FuncCall $call, Scope $scope): string|null
    {
        if ($call->name instanceof Node\Name) {
            return $this->reflectionProvider->hasFunction($call->name, $scope)
                ? \mb_strtolower($this->reflectionProvider->getFunction($call->name, $scope)->getName()) : null;
        }

        return $this->literalName($call->name, $scope);
    }

    public function methodName(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $call, Scope $scope): string|null
    {
        return $call->name instanceof Node\Identifier ? \mb_strtolower($call->name->toString()) : $this->literalName($call->name, $scope);
    }

    public function onClass(Node\Expr\MethodCall|Node\Expr\New_|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $call, Scope $scope, string $class): bool
    {
        if ($call instanceof Node\Expr\MethodCall || $call instanceof Node\Expr\NullsafeMethodCall) {
            $type = $scope->getType($call->var);

            return new ObjectType($class)->isSuperTypeOf($call instanceof Node\Expr\NullsafeMethodCall ? TypeCombinator::removeNull($type) : $type)->yes();
        }

        $name = $call->class instanceof Node\Name ? $scope->resolveName($call->class) : null;

        if ($call->class instanceof Node\Expr) {
            $name = $this->literalName($call->class, $scope);
        }

        return null !== $name && (\strcasecmp(\mb_ltrim($name, '\\'), $class) === 0
            || ($this->reflectionProvider->hasClass($name) && $this->reflectionProvider->getClass($name)->is($class)));
    }

    private function literalName(Node\Expr $expression, Scope $scope): string|null
    {
        $type = $scope->getType($expression);
        $strings = $type->getConstantStrings();

        return \count($strings) === 1 && $strings[0]->equals($type) ? \mb_strtolower(\mb_ltrim($strings[0]->getValue(), '\\')) : null;
    }
}
