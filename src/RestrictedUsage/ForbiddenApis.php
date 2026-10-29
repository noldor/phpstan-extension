<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\RestrictedUsage;

use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Configuration\ClassTraits;

final class ForbiddenApis
{
    /**
     * @param list<array{class: string, methods: list<string>}> $methods
     * @param list<string> $functions
     */
    public function __construct(
        private readonly array $methods,
        private readonly array $functions,
    ) {}

    public function matchesMethod(ClassReflection $class, string $method): bool
    {
        foreach ($this->methods as $selector) {
            $target = \mb_ltrim($selector['class'], '\\');

            if (\strcasecmp($class->getName(), $target) !== 0 && ! $class->is($target) && ! ClassTraits::contains($class, $target)) {
                continue;
            }

            foreach ($selector['methods'] as $name) {
                if ('*' === $name || \strcasecmp($name, $method) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    public function matchesFunction(string $function): bool
    {
        foreach ($this->functions as $name) {
            if (\strcasecmp(\mb_ltrim($name, '\\'), $function) === 0) {
                return true;
            }
        }

        return false;
    }
}
