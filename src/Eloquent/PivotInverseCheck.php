<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PHPStan\Type\ObjectType;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ReflectionProvider;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, MorphPivot, Pivot, Relation};

final class PivotInverseCheck
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function isCustomPivot(PivotState $state): bool
    {
        if (null === $state->pivot || ! $this->reflectionProvider->hasClass($state->pivot)) {
            return false;
        }
        $pivot = $this->reflectionProvider->getClass($state->pivot);

        return ! \in_array($state->pivot, [Pivot::class, MorphPivot::class], true) && $pivot->is($state->morph ? MorphPivot::class : Pivot::class);
    }

    public function hasCorrectInverses(PivotState $state, string $owner): bool
    {
        return null !== $state->pivot && null !== $state->related
            && $this->matches($state->pivot, $state->declaringInverse, $owner)
            && $this->matches($state->pivot, $state->relatedInverse, $state->related);
    }

    private function matches(string $pivot, string|null $method, string $target): bool
    {
        $class = $this->reflectionProvider->getClass($pivot);

        if (null === $method || ! $class->hasNativeMethod($method)) {
            return false;
        }
        $return = $class->getNativeMethod($method)->getVariants()[0]->getReturnType();

        if (! new ObjectType(BelongsTo::class)->isSuperTypeOf($return)->yes()) {
            return false;
        }
        $related = $return->getTemplateType(Relation::class, 'TRelatedModel');

        return ! new ObjectType(Model::class)->equals($related) && $related->isSuperTypeOf(new ObjectType($target))->yes();
    }
}
