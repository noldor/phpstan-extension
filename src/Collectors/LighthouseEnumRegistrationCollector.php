<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Collectors\Collector;
use Noldors\PHPStan\Eloquent\Arguments;

/**
 * @implements Collector<Node\Expr\MethodCall, string>
 */
final class LighthouseEnumRegistrationCollector implements Collector
{
    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): string|null
    {
        if (! $node->name instanceof Node\Identifier || $node->isFirstClassCallable()) {
            return null;
        }
        $method = \mb_strtolower($node->name->toString());
        $lazy = \in_array($method, ['registerlazy', 'overwritelazy'], true);

        if ((! $lazy && ! \in_array($method, ['register', 'overwrite'], true))
            || ! new ObjectType('Nuwave\\Lighthouse\\Schema\\TypeRegistry')->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return null;
        }
        $argument = Arguments::expression($node->getArgs(), 'type', $lazy ? 1 : 0);
        $type = $lazy ? $this->callbackResult($argument) : $argument;

        $enumClass = $this->enumClassArgument($type, $scope);

        if (null === $enumClass || ($lazy && ! $this->isClassLiteral($enumClass))) {
            return null;
        }

        return Arguments::string($scope->getType($enumClass));
    }

    private function enumClassArgument(Node\Expr|null $type, Scope $scope): Node\Expr|null
    {
        if (! $type instanceof Node\Expr\New_ || $type->isFirstClassCallable() || ! $type->class instanceof Node\Name
            || 0 !== \strcasecmp($scope->resolveName($type->class), 'GraphQL\\Type\\Definition\\PhpEnumType')) {
            return null;
        }

        return Arguments::expression($type->getArgs(), 'enumClass', 0);
    }

    private function callbackResult(Node\Expr|null $callback): Node\Expr|null
    {
        if ($callback instanceof Node\Expr\ArrowFunction) {
            return $callback->expr;
        }

        if ($callback instanceof Node\Expr\Closure && 1 === \count($callback->stmts) && $callback->stmts[0] instanceof Node\Stmt\Return_) {
            return $callback->stmts[0]->expr;
        }

        return null;
    }

    private function isClassLiteral(Node\Expr $expression): bool
    {
        // A lazy callback can execute after variables in the registration scope change.
        return $expression instanceof Node\Scalar\String_
            || ($expression instanceof Node\Expr\ClassConstFetch && $expression->class instanceof Node\Name
                && $expression->name instanceof Node\Identifier && 0 === \strcasecmp($expression->name->toString(), 'class'));
    }
}
