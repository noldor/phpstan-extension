<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Configuration;

use PHPStan\Reflection\ClassReflection;

final class ClassTraits
{
    public static function contains(ClassReflection $class, string $name): bool
    {
        $name = \mb_ltrim($name, '\\');

        foreach ($class->getTraits(true) as $trait) {
            if (\strcasecmp($trait->getName(), $name) === 0) {
                return true;
            }
        }

        return false;
    }
}
