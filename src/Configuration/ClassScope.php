<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Configuration;

use PHPStan\Reflection\ClassReflection;

final class ClassScope
{
    /**
     * @param list<string> $classes
     * @param list<string> $namespaces
     * @param list<string> $excludedClasses
     * @param list<string> $excludedNamespaces
     */
    public function __construct(
        private readonly array $classes = [],
        private readonly array $namespaces = [],
        private readonly array $excludedClasses = [],
        private readonly array $excludedNamespaces = [],
    ) {}

    public function isConfigured(): bool
    {
        return [] !== $this->classes || [] !== $this->namespaces;
    }

    public function contains(ClassReflection|null $class): bool
    {
        return null !== $class
            && ! $this->matches($class, $this->excludedClasses, $this->excludedNamespaces)
            && $this->matches($class, $this->classes, $this->namespaces);
    }

    /**
     * @param list<string> $classes
     * @param list<string> $namespaces
     */
    private function matches(ClassReflection $class, array $classes, array $namespaces): bool
    {
        foreach ($classes as $name) {
            $name = \mb_ltrim($name, '\\');

            if (\strcasecmp($class->getName(), $name) === 0 || $class->is($name)) {
                return true;
            }
        }
        $name = \mb_strtolower($class->getName());

        foreach ($namespaces as $namespace) {
            $namespace = \mb_strtolower(\mb_trim($namespace, '\\'));

            if ('' !== $namespace && \str_starts_with($name, $namespace . '\\')) {
                return true;
            }
        }

        return false;
    }
}
