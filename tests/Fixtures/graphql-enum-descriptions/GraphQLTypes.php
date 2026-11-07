<?php

declare(strict_types = 1);

namespace GraphQL\Type\Definition;

abstract class Type {}

class PhpEnumType extends Type
{
    /** @param class-string<\UnitEnum> $enumClass */
    public function __construct(string $enumClass, ?string $name = null, ?string $description = null) {}
}

#[\Attribute]
class Description
{
    public function __construct(string $description) {}
}

namespace Nuwave\Lighthouse\Schema;

use GraphQL\Type\Definition\Type;

class TypeRegistry
{
    public function register(Type $type): self
    {
        return $this;
    }

    public function overwrite(Type $type): self
    {
        return $this;
    }

    public function registerLazy(string $name, callable $type): self
    {
        return $this;
    }

    public function overwriteLazy(string $name, callable $type): self
    {
        return $this;
    }
}
