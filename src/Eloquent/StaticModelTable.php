<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\AttributeReflection;

final class StaticModelTable
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly ModelConstructor $constructors,
        private readonly PropertyExceptions $dynamicConfiguration,
    ) {}

    public function resolve(string $name): string|null
    {
        if (! $this->reflectionProvider->hasClass($name)) {
            return null;
        }
        $class = $this->reflectionProvider->getClass($name);

        if (! $class->is(Model::class) || ! $this->usesNativeTable($class) || $this->dynamicConfiguration->contains($class, 'table') || $this->constructors->mutates($class, 'table')) {
            return null;
        }
        $native = $class->getNativeReflection();
        $table = $native->getDefaultProperties()['table'] ?? null;
        $declaresTable = $native->hasProperty('table') && $native->getProperty('table')->getDeclaringClass()->getName() === $name;
        $attribute = $this->nearestAttribute($class);
        $attributeName = Arguments::string($attribute?->getArgumentTypes()['name'] ?? null);

        if (! $declaresTable && null !== $this->ownAttribute($class)) {
            $table = $attributeName;
        } else {
            $table ??= $attributeName;
        }

        return null === $table ? Str::snake(Str::pluralStudly(\class_basename($name))) : (\is_string($table) ? $table : null);
    }

    private function usesNativeTable(ClassReflection $class): bool
    {
        $actual = $class->getNativeReflection()->getMethod('getTable');
        $expected = $this->reflectionProvider->getClass(Model::class)->getNativeReflection()->getMethod('getTable');

        return $actual->getFileName() === $expected->getFileName() && $actual->getStartLine() === $expected->getStartLine();
    }

    private function nearestAttribute(ClassReflection $class): AttributeReflection|null
    {
        foreach ([$class, ...$class->getParents()] as $ancestor) {
            foreach ([$ancestor, ...$ancestor->getTraits()] as $source) {
                $attribute = $this->ownAttribute($source);

                if (null !== $attribute) {
                    return $attribute;
                }
            }
        }

        return null;
    }

    private function ownAttribute(ClassReflection $class): AttributeReflection|null
    {
        foreach ($class->getAttributes() as $attribute) {
            if ('Illuminate\\Database\\Eloquent\\Attributes\\Table' === $attribute->getName()) {
                return $attribute;
            }
        }

        return null;
    }
}
