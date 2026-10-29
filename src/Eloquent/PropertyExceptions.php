<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PHPStan\Reflection\ClassReflection;

final class PropertyExceptions
{
    /**
     * @param list<array{class: string, properties: list<string>}> $entries
     */
    public function __construct(
        private readonly array $entries = [],
    ) {}

    public function contains(ClassReflection $class, string $property): bool
    {
        foreach ($this->entries as $entry) {
            if ($class->is($entry['class']) && \in_array($property, $entry['properties'], true)) {
                return true;
            }
        }

        return false;
    }
}
