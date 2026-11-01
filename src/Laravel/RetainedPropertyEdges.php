<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PHPStan\Reflection\ClassReflection;

/**
 * @phpstan-import-type ClassEdges from RetainedDependencyGraph
 */
final class RetainedPropertyEdges
{
    public function __construct(
        private readonly RetainedDependencyTypes $types,
    ) {}

    /**
     * @return ClassEdges
     */
    public function forClass(ClassReflection $class): array
    {
        $classes = [];

        for ($owner = $class; null !== $owner; $owner = $owner->getParentClass()) {
            foreach ($owner->getNativeReflection()->getProperties() as $property) {
                if ($property->isStatic() || $property->isVirtual() || $property->getDeclaringClass()->getName() !== $owner->getName()) {
                    continue;
                }
                $dependencies = $this->types->inspect($owner->getNativeProperty($property->getName())->getReadableType());
                $classes = [...$classes, ...$dependencies['classes']];

                if ($dependencies['unknown']) {
                    return ['classes' => $classes, 'reason' => 'retained property ' . $owner->getName() . '::$' . $property->getName() . ' has an unresolved object/callable type'];
                }
            }
        }

        return ['classes' => $classes, 'reason' => null];
    }
}
