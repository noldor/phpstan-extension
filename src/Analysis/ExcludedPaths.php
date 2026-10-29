<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Analysis;

use PHPStan\File\FileHelper;

final class ExcludedPaths
{
    /**
     * @param list<string> $paths
     */
    public function __construct(
        private readonly FileHelper $fileHelper,
        private readonly array $paths,
    ) {}

    public function contains(string $file): bool
    {
        $file = $this->fileHelper->normalizePath($file, '/');

        foreach ($this->paths as $path) {
            $path = \mb_rtrim($this->fileHelper->normalizePath($this->fileHelper->absolutizePath($path), '/'), '/');

            if ($file === $path || \str_starts_with($file, $path . '/')) {
                return true;
            }
        }

        return false;
    }
}
