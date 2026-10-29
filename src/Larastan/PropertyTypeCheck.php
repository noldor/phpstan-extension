<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Larastan;

use PHPStan\Type\Type;
use PHPStan\Type\NullType;
use PHPStan\Type\TypeCombinator;

final class PropertyTypeCheck
{
    public function matches(Type $expected, Type $documented, bool $reliableNullability): bool
    {
        if ($reliableNullability && $expected->isSuperTypeOf(new NullType())->yes() !== $documented->isSuperTypeOf(new NullType())->yes()) {
            return false;
        }
        $expected = TypeCombinator::removeNull($expected);
        $documented = TypeCombinator::removeNull($documented);

        if ($expected->isInteger()->yes() && $documented->isInteger()->yes()) {
            return true;
        }

        return $expected->isSuperTypeOf($documented)->yes();
    }
}
