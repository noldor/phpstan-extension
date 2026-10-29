<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

final class RuleListItems
{
    /**
     * @return array<int|string, Node\Expr>|null
     */
    public function read(Node\Expr $expression, Scope $scope): array|null
    {
        if ($expression instanceof Node\Expr\BinaryOp\Plus) {
            $left = $this->read($expression->left, $scope);
            $right = $this->read($expression->right, $scope);

            return null === $left || null === $right ? null : $left + $right;
        }

        if (! $expression instanceof Node\Expr\Array_) {
            return null;
        }
        $items = [];

        foreach ($expression->items as $item) {
            $next = $this->append($items, $item, $scope);

            if (null === $next) {
                return null;
            }
            $items = $next;
        }

        return $items;
    }

    /**
     * @param array<int|string, Node\Expr> $items
     *
     * @return array<int|string, Node\Expr>|null
     */
    private function append(array $items, Node\ArrayItem $item, Scope $scope): array|null
    {
        if ($item->unpack) {
            $unpacked = $this->read($item->value, $scope);

            return null === $unpacked ? null : [...$items, ...$unpacked];
        }

        if (null === $item->key) {
            $items[] = $item->value;

            return $items;
        }
        $keys = $scope->getType($item->key)->toArrayKey()->getConstantScalarValues();

        if (1 !== \count($keys) || ! \is_int($keys[0]) && ! \is_string($keys[0])) {
            return null;
        }
        $items[$keys[0]] = $item->value;

        return $items;
    }
}
