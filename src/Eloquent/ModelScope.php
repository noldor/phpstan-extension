<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Configuration\ClassScope;

final class ModelScope
{
    public function __construct(
        private readonly ClassScope $models,
        private readonly ClassScope $excluded,
        private readonly ClassScope $observers,
    ) {}

    public function contains(ClassReflection|null $class): bool
    {
        return null !== $class && $class->is(Model::class) && $this->models->contains($class) && ! $this->excluded->contains($class);
    }

    public function isObserver(ClassReflection $class): bool
    {
        return $this->observers->contains($class);
    }

    public function isEnabled(): bool
    {
        return $this->models->isConfigured() || $this->observers->isConfigured();
    }
}
