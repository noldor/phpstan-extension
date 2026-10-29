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
    private function missingInContext(string $field, RuleSet $set, array $messages, RuleSet|null $parent = null): array
    {
        if (null !== $set->override && $this->nativeMessage($field, $set->override, $parent ?? $set, $messages)) {
            return [];
        }
        $missing = [];
        $nestedClasses = \array_map(static fn (RuleSet $nested): string|null => $nested->override, $set->nested);

        foreach ($set->rules as $rule) {
            if (! \in_array($rule, $nestedClasses, true) && $this->names->fails($rule) && ! $this->hasMessage($field . '.' . $rule, $rule, $set, $messages)) {
                $missing[] = $field . '.' . $rule;
            }
        }

        foreach ($set->nested as $nested) {
            $missing = [...$missing, ...$this->missingInContext($field, $nested, $messages, $set)];
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

        if (null !== $this->names->nativeAlias($rule)) {
            return $this->nativeMessage(\mb_substr($key, 0, -\mb_strlen('.' . $rule)), $rule, $set, $messages);
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
    private function nativeMessage(string $field, string $class, RuleSet $set, array $messages): bool
    {
        if ($this->covered($field . '.' . $class, $messages)) {
            return true;
        }
        $alias = $this->names->nativeAlias($class);

        return null !== $alias && ! \in_array('file', $this->branches->forRule($set, $class), true) && $this->covered($field . '.' . $alias, $messages);
    }

    /**
     * @param array<string, Type> $messages
     */
    private function covered(string $key, array $messages): bool
    {
        foreach ($messages as $pattern => $type) {
            $values = $type->getConstantScalarValues();

            if ($type->isNull()->yes() || [false] === $values || [''] === $values) {
                continue;
            }

            if ($this->paths->matches($pattern, $key)) {
                return true;
            }
        }

        return false;
    }
}
