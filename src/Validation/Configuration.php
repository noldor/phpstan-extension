<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PHPStan\Reflection\ClassReflection;

final class Configuration
{
    /**
     * @param list<array{class: string, fields: list<string>}> $relationships
     * @param list<array{class: string, fields: list<string>}> $inputValidators
     */
    public function __construct(
        private readonly array $relationships,
        private readonly array $inputValidators,
    ) {}

    /**
     * @return list<string>
     */
    public function relationships(ClassReflection $class): array
    {
        return $this->fields($class, $this->relationships);
    }

    /**
     * @return list<string>
     */
    public function inputFields(ClassReflection $class): array
    {
        return $this->fields($class, $this->inputValidators);
    }

    /**
     * @param list<array{class: string, fields: list<string>}> $configuration
     *
     * @return list<string>
     */
    private function fields(ClassReflection $class, array $configuration): array
    {
        $fields = [];

        foreach ($configuration as $entry) {
            if ($class->is(\mb_ltrim($entry['class'], '\\'))) {
                $fields = [...$fields, ...$entry['fields']];
            }
        }

        return \array_values(\array_unique($fields));
    }
}
