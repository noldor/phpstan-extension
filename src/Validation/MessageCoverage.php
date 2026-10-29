<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;

final class MessageCoverage
{
    public function __construct(
        private readonly RuleNames $names,
        private readonly Paths $paths,
        private readonly RuleExpressions $expressions,
        private readonly SizeMessageBranches $branches,
    ) {}

    /**
     * @param array<string, Type>|null $messages
     *
     * @return list<string>
     */
    public function missing(ClassReflection $class, Scope $scope, array|null $messages): array
    {
        $rules = $this->expressions->read($class, $scope);

        if (null === $rules || null === $messages) {
            return [];
        }
        $missing = [];

        foreach ($rules as $field => $set) {
            $missing = [...$missing, ...$this->missingInContext($field, $set, $messages)];
        }

        return \array_values(\array_unique($missing));
    }

    /**
     * @param array<string, Type> $messages
     *
     * @return list<string>
     */
    private function missingInContext(string $field, RuleSet $set, array $messages): array
    {
        $missing = [];

        foreach ($set->rules as $rule) {
            if ($this->names->fails($rule) && ! $this->hasMessage($field . '.' . $rule, $rule, $set, $messages)) {
                $missing[] = $field . '.' . $rule;
            }
        }

        foreach ($set->nested as $nested) {
            $missing = [...$missing, ...$this->missingInContext($field, $nested, $messages)];
        }

        return $missing;
    }

    /**
     * @param array<string, Type> $messages
     */
    private function hasMessage(string $key, string $rule, RuleSet $set, array $messages): bool
    {
        if ($this->covered($key, $messages)) {
            return true;
        }

        if (! $this->branches->isSizeRule($rule)) {
            return false;
        }

        foreach ($this->branches->forRule($set, $rule) as $branch) {
            if (! $this->covered($key . '.' . $branch, $messages)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, Type> $messages
     */
    private function covered(string $key, array $messages): bool
    {
        foreach ($messages as $pattern => $type) {
            $strings = $type->getConstantStrings();

            if ($type->isNull()->yes() || 1 === \count($strings) && '' === $strings[0]->getValue()) {
                continue;
            }

            if ($this->paths->matches($pattern, $key)) {
                return true;
            }
        }

        return false;
    }
}
