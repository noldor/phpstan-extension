<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use ReflectionIntersectionType;
use PHPStan\Reflection\ClassReflection;

/**
 * @phpstan-import-type ClassEdges from RetainedDependencyGraph
 */
final class RetainedConstructorEdges
{
    public function __construct(
        private readonly RetainedDependencyTypes $types,
    ) {}

    /**
     * @return ClassEdges
     */
    public function forClass(ClassReflection $class, bool $reviewedFactory): array
    {
        if (! $reviewedFactory && $this->hasCustomConstruction($class)) {
            return ['classes' => [], 'reason' => 'construction attributes or self-building behavior require a reviewed finite factory declaration'];
        }

        if (! $class->hasConstructor()) {
            return ['classes' => [], 'reason' => null];
        }

        if (! $reviewedFactory && ! $class->getConstructor()->isPublic()) {
            return ['classes' => [], 'reason' => 'non-public constructor requires reviewed factory construction'];
        }
        $native = $class->getNativeReflection()->getConstructor();
        $classes = [];

        foreach ($class->getConstructor()->getVariants() as $variant) {
            foreach ($variant->getParameters() as $index => $parameter) {
                $dependencies = $this->types->inspect($parameter->getType());
                $classes = [...$classes, ...$dependencies['classes']];
                $declaration = $native?->getParameters()[$index] ?? null;

                if ($dependencies['unknown'] || (! $reviewedFactory && $this->unresolvedParameter($declaration, $parameter->isOptional()))) {
                    return ['classes' => $classes, 'reason' => 'constructor parameter $' . $parameter->getName() . ' needs explicit construction/lifetime review'];
                }
            }
        }

        return ['classes' => $classes, 'reason' => null];
    }

    public function hasCustomConstruction(ClassReflection $class): bool
    {
        if ($class->is(\Illuminate\Contracts\Container\SelfBuilding::class)) {
            return true;
        }

        foreach ([$class, ...$class->getParents()] as $owner) {
            foreach ($owner->getAttributes() as $attribute) {
                if (\str_starts_with($attribute->getName(), 'Illuminate\\Container\\Attributes\\')) {
                    return true;
                }
            }
        }

        return false;
    }

    private function unresolvedParameter(ReflectionParameter|null $parameter, bool $optional): bool
    {
        if (null === $parameter || [] !== $parameter->getAttributes()) {
            return true;
        }
        $type = $parameter->getType();

        if ($type instanceof ReflectionUnionType || $type instanceof ReflectionIntersectionType) {
            return true;
        }

        return ! $optional && (! $type instanceof ReflectionNamedType || $type->isBuiltin());
    }
}
