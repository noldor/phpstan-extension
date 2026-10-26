<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Configuration;

use PHPStan\Reflection\ClassReflection;

final class ClassScope
{
    /**
     * @param list<string> $classes
     * @param list<string> $namespaces
     */
    public function __construct(
        private readonly array $classes = [],
        private readonly array $namespaces = [],
    ) {}

    public function contains(ClassReflection|null $class): bool
    {
        if (null === $class) {
            return false;
        }

        foreach ($this->classes as $name) {
            $name = \mb_ltrim($name, '\\');

            if (0 === \strcasecmp($class->getName(), $name) || $class->is($name)) {
                return true;
            }
        }

        $name = \mb_strtolower($class->getName());

        foreach ($this->namespaces as $namespace) {
            $namespace = \mb_strtolower(\mb_trim($namespace, '\\'));

            if ('' !== $namespace && \str_starts_with($name, $namespace . '\\')) {
                return true;
            }
        }

        return false;
    }
}
