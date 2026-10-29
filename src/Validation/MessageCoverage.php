<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PHPStan\Type\Type;

final class MessageCoverage
{
    public function __construct(
        private readonly RuleNames $names,
        private readonly Paths $paths,
    ) {}

    /**
     * @param array<string, Type>|null $rules
     * @param array<string, Type>|null $messages
     *
     * @return list<string>
     */
    public function missing(array|null $rules, array|null $messages): array
    {
        if (null === $rules || null === $messages) {
            return [];
        }
        $missing = [];

        foreach ($rules as $field => $type) {
            foreach ($this->names->failingRules($type) as $rule) {
                $key = $field . '.' . $rule;

                if (! $this->covered($key, $messages)) {
                    $missing[] = $key;
                }
            }
        }

        return $missing;
    }

    /**
     * @param array<string, Type> $messages
     */
    private function covered(string $key, array $messages): bool
    {
        foreach ($messages as $pattern => $type) {
            $strings = $type->getConstantStrings();

            if (1 === \count($strings) && '' === $strings[0]->getValue()) {
                continue;
            }

            if ($this->paths->matches($pattern, $key) || \str_starts_with($pattern, $key . '.')) {
                return true;
            }
        }

        return false;
    }
}
