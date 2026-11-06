<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Type\Constant\ConstantStringType;

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
        if (null !== $set->override) {
            $override = $this->nativeMessage($field, $set->override, $parent ?? $set, $messages);

            if (null !== $override) {
                return $override ? [] : [$field . '.' . $set->override];
            }
        }
        $missing = [];
        $nestedClasses = \array_map(static fn (RuleSet $nested): string|null => $nested->override, $set->nested);

        foreach ($set->rules as $rule) {
            if (! \in_array($rule, $nestedClasses, true) && $this->names->fails($rule) && ! $this->hasMessage($field, $rule, $set, $messages)) {
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
    private function hasMessage(string $field, string $rule, RuleSet $set, array $messages): bool
    {
        if (null !== $this->names->nativeAlias($rule)) {
            return true === $this->nativeMessage($field, $rule, $set, $messages);
        }
        $type = $this->localMessage($field, $rule, [$field . '.' . $rule, $rule, $field], $messages);

        if (null === $type) {
            return false;
        }

        if (! $type->isArray()->yes()) {
            return $this->present($type);
        }

        if (! $this->branches->isSizeRule($rule)) {
            return false;
        }

        foreach ($this->branches->forRule($set, $rule) as $branch) {
            $offset = new ConstantStringType($branch);

            if (! $type->hasOffsetValueType($offset)->yes() || ! $this->present($type->getOffsetValueType($offset))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, Type> $messages
     *
     * @return bool|null null means Laravel delegates to the rule's own messages
     */
    private function nativeMessage(string $field, string $class, RuleSet $set, array $messages): bool|null
    {
        $keys = [$field . '.' . $class, $class, $field];
        $alias = $this->names->nativeAlias($class);

        if (null !== $alias && (! $this->branches->hasFileRules($set) || ! \in_array('file', $this->branches->forRule($set, $class), true))) {
            $keys[] = $field . '.' . $alias;
        }
        $type = $this->localMessage($field, $class, $keys, $messages);

        return null === $type || $type->isNull()->yes() ? null : $this->nativePayload($type);
    }

    /**
     * @param list<string> $keys
     * @param array<string, Type> $messages
     */
    private function localMessage(string $field, string $rule, array $keys, array $messages): Type|null
    {
        foreach ($keys as $key) {
            foreach ($messages as $pattern => $type) {
                if (! $this->paths->matches($pattern, $key)) {
                    continue;
                }

                return $this->messageEntry($type, $pattern, $field, $rule);
            }
        }

        return null;
    }

    private function messageEntry(Type $type, string $pattern, string $field, string $rule): Type|null
    {
        if ($type->isArray()->yes() && ($pattern === $field || \str_contains($pattern, '*'))) {
            $offset = new ConstantStringType($rule);

            if (! $type->hasOffsetValueType($offset)->yes()) {
                return null;
            }
            $type = $type->getOffsetValueType($offset);
        }

        return $type;
    }

    private function nativePayload(Type $type): bool
    {
        if (! $type->isArray()->yes()) {
            return ! $type->isString()->no() && ! $type->toBoolean()->isFalse()->yes();
        }
        $arrays = $type->getConstantArrays();

        if (1 !== \count($arrays) || \count($arrays[0]->getOptionalKeys()) === \count($arrays[0]->getValueTypes())) {
            return false;
        }

        foreach ($arrays[0]->getValueTypes() as $value) {
            if ($value->isString()->no() || ! $this->present($value)) {
                return false;
            }
        }

        return true;
    }

    private function present(Type $type): bool
    {
        $values = $type->getConstantScalarValues();

        return ! $type->isNull()->yes() && [false] !== $values && [''] !== $values;
    }
}
