<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use Noldors\PHPStan\Schema\ForeignKeyBody;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @implements Rule<Node\FunctionLike>
 */
final class LaravelForeignKeyRule implements Rule
{
    /**
     * @param list<string> $paths
     * @param list<string> $excludePaths
     */
    public function __construct(
        private readonly FileHelper $fileHelper,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly array $paths,
        private readonly array $excludePaths,
    ) {}

    public function getNodeType(): string
    {
        return Node\FunctionLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->matchesPaths($scope->getFile(), $this->paths) || $this->matchesPaths($scope->getFile(), $this->excludePaths)) {
            return [];
        }

        return new ForeignKeyBody($scope, $this->reflectionProvider)->errors($node);
    }

    /**
     * @param list<string> $paths
     */
    private function matchesPaths(string $file, array $paths): bool
    {
        $file = $this->fileHelper->normalizePath($file, '/');

        foreach ($paths as $path) {
            $path = \mb_rtrim($this->fileHelper->normalizePath($this->fileHelper->absolutizePath($path), '/'), '/');

            if ($file === $path || \str_starts_with($file, $path . '/')) {
                return true;
            }
        }

        return false;
    }
}
