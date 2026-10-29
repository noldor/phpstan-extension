<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

final class FieldOrder
{
    public function __construct(
        private readonly Paths $paths,
    ) {}

    /**
     * @param list<string> $fields
     * @param list<string> $relationships
     */
    public function rulesAreOrdered(array $fields, array $relationships): bool
    {
        $previous = '';
        $closed = [];
        $inRelationships = false;
        $positions = \array_flip($fields);

        foreach ($fields as $index => $field) {
            $group = $this->relationship($field, $relationships);

            if ($inRelationships && '' === $group || \in_array($group, $closed, true) || ! $this->parentsPrecede($field, $index, $positions)) {
                return false;
            }

            if ($previous !== $group) {
                $closed[] = $previous;
            }
            $previous = $group;
            $inRelationships = $inRelationships || '' !== $group;
        }

        return true;
    }

    /**
     * @param list<string> $fields
     * @param list<string> $keys
     */
    public function followRules(array $fields, array $keys, bool $messages): bool
    {
        $previous = -1;

        foreach ($keys as $key) {
            $position = $messages ? $this->messagePosition($fields, $key) : $this->position($fields, $key);

            if (null === $position) {
                continue;
            }

            if ($position < $previous) {
                return false;
            }
            $previous = $position;
        }

        return true;
    }

    /**
     * @param list<string> $relationships
     */
    private function relationship(string $field, array $relationships): string
    {
        foreach ($relationships as $relationship) {
            if ($field === $relationship || \str_starts_with($field, $relationship . '.')) {
                return $relationship;
            }
        }

        return '';
    }

    /**
     * @param array<string, int> $positions
     */
    private function parentsPrecede(string $field, int $index, array $positions): bool
    {
        $parts = \explode('.', $field);
        \array_pop($parts);

        while ([] !== $parts) {
            if (($positions[\implode('.', $parts)] ?? -1) > $index) {
                return false;
            }
            \array_pop($parts);
        }

        return true;
    }

    /**
     * @param list<string> $fields
     */
    private function position(array $fields, string $path): int|null
    {
        $exact = \array_search($path, $fields, true);

        if (false !== $exact) {
            return $exact;
        }

        foreach ($fields as $position => $field) {
            if ($this->paths->matches($field, $path)) {
                return $position;
            }
        }

        return null;
    }

    /**
     * @param list<string> $fields
     */
    private function messagePosition(array $fields, string $key): int|null
    {
        $parts = \explode('.', $key);

        while ([] !== $parts) {
            $position = $this->position($fields, \implode('.', $parts));

            if (null !== $position) {
                return $position;
            }
            \array_pop($parts);
        }

        return null;
    }
}
