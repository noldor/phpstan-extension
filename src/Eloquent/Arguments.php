<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Constant\ConstantIntegerType;

final class Arguments
{
    /**
     * @param array<Node\Arg> $arguments
     */
    public static function expression(array $arguments, string $name, int $position): Node\Expr|null
    {
        foreach ($arguments as $argument) {
            if ($argument->name?->toString() === $name) {
                return $argument->value;
            }
        }
        $argument = \array_values($arguments)[$position] ?? null;

        return null !== $argument && null === $argument->name && ! $argument->unpack ? $argument->value : null;
    }

    /**
     * @param array<Node\Arg> $arguments
     */
    public static function type(array $arguments, Scope $scope, string $name, int $position): Type|null
    {
        foreach (\array_values($arguments) as $index => $argument) {
            if ($argument->name?->toString() === $name || (! $argument->unpack && null === $argument->name && $index === $position)) {
                return $scope->getType($argument->value);
            }

            if ($argument->unpack) {
                $type = $scope->getType($argument->value);

                foreach ([new ConstantStringType($name), new ConstantIntegerType($position - $index)] as $key) {
                    if ($type->hasOffsetValueType($key)->yes()) {
                        return $type->getOffsetValueType($key);
                    }
                }
            }
        }

        return null;
    }

    public static function string(Type|null $type): string|null
    {
        $strings = $type?->getConstantStrings() ?? [];

        return \count($strings) === 1 && $strings[0]->equals($type) ? $strings[0]->getValue() : null;
    }

    /**
     * @return list<string>
     */
    public static function strings(Type|null $type): array
    {
        if (null === $type) {
            return [];
        }
        $strings = $type->getConstantStrings();

        if ([] !== $strings && TypeCombinator::union(...$strings)->isSuperTypeOf($type)->yes()) {
            return \array_map(static fn (ConstantStringType $string): string => $string->getValue(), $strings);
        }
        $result = [];

        foreach ($type->getConstantArrays() as $array) {
            foreach ($array->getValueTypes() as $value) {
                $result = [...$result, ...self::strings($value)];
            }
        }

        return $result;
    }
}
