<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\ObjectType;

final class JobPayloadTypes
{
    public function containsModel(Type $type): bool
    {
        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $member) {
                if ($this->containsModel($member)) {
                    return true;
                }
            }

            return false;
        }

        if (new ObjectType('Illuminate\\Database\\Eloquent\\Model')->isSuperTypeOf($type)->yes() || new ObjectType('Illuminate\\Database\\Eloquent\\Collection')->isSuperTypeOf($type)->yes()) {
            return true;
        }

        if ($type->isArray()->yes() || new ObjectType(\Illuminate\Support\Collection::class)->isSuperTypeOf($type)->yes()) {
            return $this->containsModel($type->getIterableValueType());
        }

        return false;
    }
}
