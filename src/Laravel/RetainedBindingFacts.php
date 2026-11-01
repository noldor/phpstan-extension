<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Type\TypeCombinator;

/**
 * @phpstan-type BindingFact array{abstracts: list<string>, concretes: list<string>, kind: string, line: int, reason: string, inherited?: bool, canonical?: bool, inheritedOnly?: bool}
 */
final class RetainedBindingFacts
{
    /**
     * @return list<string>
     */
    public function strings(Type|null $type): array
    {
        $strings = $type?->getConstantStrings() ?? [];

        if (null === $type || [] === $strings || ! TypeCombinator::union(...$strings)->isSuperTypeOf($type)->yes()) {
            return [];
        }

        return \array_map(static fn ($string): string => $string->getValue(), $strings);
    }

    public function argument(Node\Expr\CallLike $call, Scope $scope, int $position, string $name): Type|null
    {
        foreach ($call->getArgs() as $index => $argument) {
            if ($argument->unpack) {
                return null;
            }

            if ($argument->name?->toString() === $name || (null === $argument->name && $index === $position)) {
                return $scope->getType($argument->value);
            }
        }

        return null;
    }

    public function hasUnpack(Node\Expr\CallLike $call): bool
    {
        foreach ($call->getArgs() as $argument) {
            if ($argument->unpack) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<BindingFact>
     */
    public function map(Type|null $type, int $line, bool $singletonList, string $kind = 'class'): array
    {
        $arrays = $type?->getConstantArrays() ?? [];

        if (null === $type || [] === $arrays || ! TypeCombinator::union(...$arrays)->isSuperTypeOf($type)->yes()) {
            return [['abstracts' => [], 'concretes' => [], 'kind' => 'opaque', 'line' => $line, 'reason' => 'unresolved binding map']];
        }
        $bindings = [];

        foreach ($arrays as $array) {
            foreach ($array->getKeyTypes() as $index => $key) {
                $concretes = $this->strings($array->getValueTypes()[$index]);
                $abstracts = $singletonList && $key->isInteger()->yes() ? $concretes : $this->strings($key);

                if ($array->getValueTypes()[$index]->isNull()->yes() && [] !== $abstracts) {
                    $concretes = $abstracts;
                }
                $bindings[] = ['abstracts' => $abstracts, 'concretes' => $concretes, 'kind' => [] !== $concretes ? $kind : 'opaque', 'line' => $line, 'reason' => 'binding map construction'];
            }
        }

        return $bindings;
    }

    /**
     * @return list<string>
     */
    public function targets(Type|null $type): array
    {
        $strings = $this->strings($type);

        if ([] !== $strings || null === $type) {
            return $strings;
        }
        $arrays = $type->getConstantArrays();

        if ([] === $arrays || ! TypeCombinator::union(...$arrays)->isSuperTypeOf($type)->yes()) {
            return [];
        }
        $targets = [];

        foreach ($arrays as $array) {
            foreach ($array->getValueTypes() as $value) {
                $strings = $this->strings($value);

                if ([] === $strings) {
                    return [];
                }
                $targets = [...$targets, ...$strings];
            }
        }

        return $targets;
    }
}
