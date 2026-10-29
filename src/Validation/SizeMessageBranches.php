<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

final class SizeMessageBranches
{
    private const array RULES = ['size', 'between', 'min', 'max', 'gt', 'lt', 'gte', 'lte'];

    private const array COMPARISONS = ['gt', 'lt', 'gte', 'lte'];

    public function isSizeRule(string $rule): bool
    {
        return \in_array($rule, self::RULES, true);
    }

    /**
     * @return list<string>
     */
    public function forRule(RuleSet $set, string $rule): array
    {
        if ([] !== \array_intersect($set->rules, ['numeric', 'integer', 'decimal'])) {
            return ['numeric'];
        }
        $numeric = $this->canCompareNumerically($set, $rule) ? ['numeric'] : [];

        return \array_values(\array_unique([...$numeric, ...$this->remainingBranches($set, $rule)]));
    }

    /**
     * @return list<string>
     */
    private function remainingBranches(RuleSet $set, string $rule): array
    {
        if ([] !== \array_intersect($set->rules, ['array', 'list'])) {
            return $set->unknown ? ['numeric', 'array'] : ['array'];
        }

        if ($set->unknown) {
            return ['numeric', 'array', 'string', 'file'];
        }
        $position = \array_search($rule, $set->rules, true);
        $previous = false === $position ? [] : \array_slice($set->rules, 0, $position);

        if (\in_array('bail', $set->rules, true)) {
            if (\in_array('string', $previous, true)) {
                return ['string'];
            }

            if ([] !== \array_intersect($previous, ['file', 'image'])) {
                return ['file'];
            }
        }

        return ['string', 'file'];
    }

    private function canCompareNumerically(RuleSet $set, string $rule): bool
    {
        if (! \in_array($rule, self::COMPARISONS, true)) {
            return false;
        }
        $position = \array_search($rule, $set->rules, true);
        $previous = false === $position ? [] : \array_slice($set->rules, 0, $position);

        return ! \in_array('bail', $set->rules, true) || [] === \array_intersect($previous, ['file', 'image', 'array', 'list']);
    }
}
