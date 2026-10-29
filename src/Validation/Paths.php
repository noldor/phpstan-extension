<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PHPStan\Type\Type;

final class Paths
{
    public function __construct(
        private readonly MethodArrays $arrays,
    ) {}

    public function matches(string $pattern, string $path): bool
    {
        $pattern = \str_replace('\\*', '[^.]*', \preg_quote($pattern, '#'));

        return 1 === \preg_match('#^' . $pattern . '\\z#u', $path);
    }

    /**
     * @param array<string, Type>|null $entries
     *
     * @return array<string, Type>|null
     */
    public function flatten(array|null $entries): array|null
    {
        if (null === $entries) {
            return null;
        }
        $flattened = [];

        foreach ($entries as $key => $value) {
            $nested = $this->arrays->entries($value);

            if (null === $nested) {
                $flattened[$key] = $value;

                continue;
            }

            foreach ($this->flatten($nested) ?? [] as $suffix => $type) {
                $flattened[$key . '.' . $suffix] = $type;
            }
        }

        return $flattened;
    }
}
