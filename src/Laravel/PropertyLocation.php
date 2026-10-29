<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PhpParser\Node;

final class PropertyLocation
{
    public static function inClass(Node\Stmt\Class_ $class, string $name): int
    {
        foreach ($class->getProperties() as $property) {
            foreach ($property->props as $item) {
                if ($item->name->toString() === $name) {
                    return $item->getStartLine();
                }
            }
        }
        $constructor = $class->getMethod('__construct');

        foreach (null === $constructor ? [] : $constructor->params as $parameter) {
            if (0 !== $parameter->flags && $parameter->var instanceof Node\Expr\Variable && $parameter->var->name === $name) {
                return $parameter->getStartLine();
            }
        }

        return $class->getStartLine();
    }
}
