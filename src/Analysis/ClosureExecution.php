<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Analysis;

use PhpParser\Node;

final class ClosureExecution
{
    public static function isImmediatelyInvoked(Node $node, Node|null $parent): bool
    {
        if ((! $node instanceof Node\Expr\Closure && ! $node instanceof Node\Expr\ArrowFunction)
            || ! $parent instanceof Node\Expr\FuncCall || $parent->name !== $node || $parent->isFirstClassCallable()) {
            return false;
        }

        $body = $node instanceof Node\Expr\Closure ? $node->stmts : [$node->expr];

        foreach ($body as $statement) {
            if (self::containsYield($statement)) {
                return false;
            }
        }

        return true;
    }

    private static function containsYield(Node $node): bool
    {
        if ($node instanceof Node\Expr\Yield_ || $node instanceof Node\Expr\YieldFrom) {
            return true;
        }

        if ($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike) {
            return false;
        }

        foreach ($node->getSubNodeNames() as $name) {
            $child = $node->{$name};

            foreach (\is_array($child) ? $child : [$child] as $item) {
                if ($item instanceof Node && self::containsYield($item)) {
                    return true;
                }
            }
        }

        return false;
    }
}
