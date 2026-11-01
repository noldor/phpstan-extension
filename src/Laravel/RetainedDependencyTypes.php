<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use Closure;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Reflection\ClassReflection;

/**
 * @phpstan-type DependencyTypes array{classes: list<ClassReflection>, unknown: bool}
 */
final class RetainedDependencyTypes
{
    /**
     * @param array<string, true> $seen
     * @param array<string, string> $objects
     *
     * @return DependencyTypes
     */
    public function inspect(Type $type, array $seen = [], array $objects = []): array
    {
        $key = $type->describe(VerbosityLevel::precise());

        if (isset($seen[$key])) {
            return ['classes' => $type->getObjectClassReflections(), 'unknown' => [] === $type->getObjectClassReflections()];
        }
        $seen[$key] = true;

        if ($type instanceof UnionType) {
            $result = ['classes' => [], 'unknown' => false];

            foreach ($type->getTypes() as $member) {
                $part = $this->inspect($member, $seen, $objects);
                $result['classes'] = [...$result['classes'], ...$part['classes']];
                $result['unknown'] = $result['unknown'] || $part['unknown'];
            }

            return $result;
        }

        if ($type->isArray()->yes()) {
            return $this->inspect($type->getIterableValueType(), $seen, $objects);
        }

        if ($type->isScalar()->yes() || $type->isNull()->yes() || $type instanceof \PHPStan\Type\NeverType) {
            return ['classes' => [], 'unknown' => false];
        }

        if ($type instanceof TemplateType) {
            return ['classes' => [], 'unknown' => true];
        }

        if ($type->isIterable()->yes() && ! $type->isObject()->yes()) {
            return $this->inspect($type->getIterableValueType(), $seen, $objects);
        }

        if ($type->isObject()->yes()) {
            return $this->objectDependencies($type, $key, $seen, $objects);
        }

        return ['classes' => [], 'unknown' => true];
    }

    /**
     * @param array<string, true> $seen
     * @param array<string, string> $objects
     *
     * @return DependencyTypes
     */
    private function objectDependencies(Type $type, string $key, array $seen, array $objects): array
    {
        if (1 !== \count($type->getObjectClassNames()) || 1 !== \count($type->getObjectClassReflections())) {
            return ['classes' => [], 'unknown' => true];
        }
        $name = $type->getObjectClassNames()[0];

        if (isset($objects[$name]) && $objects[$name] !== $key) {
            return ['classes' => [], 'unknown' => true];
        }
        $objects[$name] = $key;
        $values = $type->isIterable()->yes() ? $this->inspect($type->getIterableValueType(), $seen, $objects) : ['classes' => [], 'unknown' => false];

        return ['classes' => [...$type->getObjectClassReflections(), ...$values['classes']], 'unknown' => $values['unknown'] || \in_array(Closure::class, $type->getObjectClassNames(), true)];
    }
}
