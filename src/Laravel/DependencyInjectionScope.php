<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use Noldors\PHPStan\Configuration\ClassScope;

final class DependencyInjectionScope
{
    /**
     * @param list<string> $excludedPaths
     */
    public function __construct(
        private readonly ClassScope $classes,
        private readonly FileHelper $fileHelper,
        private readonly array $excludedPaths,
    ) {}

    public function contains(Scope $scope): bool
    {
        if (! $this->classes->contains($scope->getClassReflection())) {
            return false;
        }

        $file = $this->fileHelper->normalizePath($scope->getFile(), '/');

        foreach ($this->excludedPaths as $path) {
            $path = \mb_rtrim($this->fileHelper->normalizePath($this->fileHelper->absolutizePath($path), '/'), '/');

            if ($file === $path || \str_starts_with($file, $path . '/')) {
                return false;
            }
        }

        return true;
    }
}
