<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PHPStan\Type\Type;
use PHPStan\Type\NullType;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Configuration\ClassScope;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;

final class QueuePolicy
{
    public function __construct(
        private readonly ClassScope $jobs,
        private readonly ClassScope $observers,
        private readonly InitializerExprTypeResolver $initializers,
    ) {}

    public function isJob(ClassReflection $class): bool
    {
        return $this->jobs->contains($class) && ! $this->isObserver($class);
    }

    public function isObserver(ClassReflection $class): bool
    {
        return $this->observers->contains($class);
    }

    public function classOf(Type $type): ClassReflection|null
    {
        $classes = $type->getObjectClassReflections();

        return \count($classes) === 1 ? $classes[0] : null;
    }

    public function hasPublicFlag(ClassReflection $class): bool
    {
        if (! $class->hasNativeProperty('afterCommit')) {
            return false;
        }
        $property = $class->getNativeProperty('afterCommit');

        return $property->isPublic() && ! $property->isStatic();
    }

    public function defaultFlag(ClassReflection $class): Type
    {
        if (! $this->hasPublicFlag($class)) {
            return new NullType();
        }
        $property = $class->getNativeReflection()->getProperty('afterCommit');
        $initializer = $property->getBetterReflection()->getDefaultValueExpression();

        if (null === $initializer) {
            return new NullType();
        }

        return $this->initializers->getType($initializer, InitializerExprContext::fromClassReflection($class->getNativeProperty('afterCommit')->getDeclaringClass()));
    }
}
