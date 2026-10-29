<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Larastan;

use Attribute;
use PHPStan\Type\Type;
use Illuminate\Support\Str;
use PHPStan\Type\ObjectType;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Eloquent\AccessorShape;
use Larastan\Larastan\Properties\ModelHelper;
use Noldors\PHPStan\Eloquent\ModelConstructor;
use Larastan\Larastan\Support\CollectionHelper;
use Noldors\PHPStan\Eloquent\PropertyExceptions;
use Illuminate\Database\Eloquent\Relations\Relation;
use Larastan\Larastan\Properties\ModelPropertyHelper;
use Illuminate\Database\Eloquent\Casts\Attribute as EloquentAttribute;

final class ModelPropertyMetadata
{
    public function __construct(
        private readonly ModelPropertyHelper $properties,
        private readonly CollectionHelper $collections,
        private readonly AccessorShape $accessors,
        private readonly PropertyExceptions $dynamicConfiguration,
        private readonly ModelConstructor $constructors,
    ) {}

    /**
     * @return list<string>|null
     */
    public function databaseNames(ClassReflection $class): array|null
    {
        if ($this->dynamicConfiguration->contains($class, 'table') || $this->dynamicConfiguration->contains($class, 'connection') || $this->constructors->mutates($class, 'table') || $this->constructors->mutates($class, 'connection')) {
            return null;
        }

        return $this->properties->getDatabasePropertyNames($class);
    }

    public function isRelation(ClassReflection $class, string $name): bool
    {
        return $class->hasNativeMethod($name) && new ObjectType(Relation::class)->isSuperTypeOf($class->getNativeMethod($name)->getVariants()[0]->getReturnType())->yes();
    }

    public function keyName(ClassReflection $class): string|null
    {
        if ($this->constructors->mutates($class, 'primaryKey') || $this->dynamicConfiguration->contains($class, 'primaryKey')) {
            return null;
        }

        return ModelHelper::newInstanceWithoutConstructor($class)->getKeyName();
    }

    public function isAccessor(ClassReflection $class, string $name): bool
    {
        return null !== $this->accessorType($class, $name) || $this->properties->hasAccessor($class, $name, false);
    }

    public function isReadableAccessor(ClassReflection $class, string $name): bool
    {
        return $class->hasNativeMethod('get' . Str::studly($name) . Attribute::class)
            || (null !== $this->accessorType($class, $name) && true === $this->accessors->hasGetter($class, Str::camel($name)));
    }

    public function isReadonlyAccessor(ClassReflection $class, string $name): bool
    {
        if ($class->hasNativeMethod('set' . Str::studly($name) . Attribute::class)) {
            return false;
        }
        $type = $this->accessorType($class, $name);

        if (null !== $type) {
            return true === $this->accessors->getterOnly($class, Str::camel($name));
        }

        return $this->properties->hasAccessor($class, $name, false);
    }

    public function readableType(ClassReflection $class, string $name, bool $database): Type|null
    {
        $legacy = 'get' . Str::studly($name) . Attribute::class;

        if ($class->hasNativeMethod($legacy)) {
            return $class->getNativeMethod($legacy)->getVariants()[0]->getReturnType();
        }
        $accessor = $this->accessorType($class, $name);

        if (null !== $accessor) {
            $getter = $this->accessors->hasGetter($class, Str::camel($name));

            if (null === $getter) {
                return null;
            }

            if ($getter || ! $database) {
                return $accessor->getTemplateType(EloquentAttribute::class, 'TGet');
            }
        }

        if ($database) {
            return $this->properties->getDatabaseProperty($class, $name)->getReadableType();
        }

        if ($this->isRelation($class, $name)) {
            $return = $class->getNativeMethod($name)->getVariants()[0]->getReturnType();

            return $this->collections->replaceCollectionsInType($return->getTemplateType(Relation::class, 'TResult'));
        }

        return null;
    }

    private function accessorType(ClassReflection $class, string $name): Type|null
    {
        $method = Str::camel($name);

        if (! $class->hasNativeMethod($method) || $class->getNativeMethod($method)->isPrivate()) {
            return null;
        }
        $type = $class->getNativeMethod($method)->getVariants()[0]->getReturnType();

        return new ObjectType(EloquentAttribute::class)->isSuperTypeOf($type)->yes() ? $type : null;
    }
}
