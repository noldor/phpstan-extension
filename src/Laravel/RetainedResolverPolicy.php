<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PHPStan\File\FileHelper;
use Noldors\PHPStan\Configuration\ClassScope;

/**
 * @phpstan-type Implementation array{abstract: string, concretes: list<string>}
 *
 * @phpstan-import-type BindingFact from RetainedBindingFacts
 */
final class RetainedResolverPolicy
{
    /**
     * @param list<string> $bindingSourcePaths
     * @param list<string> $unsafeClasses
     * @param list<string> $safeClasses
     * @param list<Implementation> $implementations
     * @param list<Implementation> $reviewedFactories
     */
    public function __construct(
        public readonly ClassScope $roots,
        private readonly FileHelper $files,
        private readonly array $bindingSourcePaths,
        public readonly array $unsafeClasses,
        private readonly array $safeClasses,
        private readonly array $implementations,
        private readonly array $reviewedFactories,
    ) {}

    public static function key(string $class): string
    {
        return \mb_strtolower(\mb_ltrim($class, '\\'));
    }

    public function collectsBindings(string $file): bool
    {
        if (! $this->roots->isConfigured()) {
            return false;
        }
        $file = $this->files->normalizePath($file, '/');

        foreach ($this->bindingSourcePaths as $path) {
            $path = \mb_rtrim($this->files->normalizePath($this->files->absolutizePath($path), '/'), '/');

            if ($file === $path || \str_starts_with($file, $path . '/')) {
                return true;
            }
        }

        return false;
    }

    public function hasBindingSources(): bool
    {
        return [] !== $this->bindingSourcePaths;
    }

    public function isSafeBoundary(string $class): bool
    {
        return \in_array(self::key($class), \array_map(self::key(...), $this->safeClasses), true);
    }

    /**
     * @return list<string>
     */
    public function implementations(string $abstract): array
    {
        return $this->products($abstract, $this->implementations);
    }

    /**
     * @return list<string>
     */
    public function reviewedProducts(string $abstract): array
    {
        return $this->products($abstract, $this->reviewedFactories);
    }

    /**
     * @param list<BindingFact> $bindings
     *
     * @return list<string>
     */
    public function resolutionProducts(string $abstract, array $bindings): array
    {
        $products = $this->implementations($abstract);

        foreach ($bindings as $binding) {
            if (\in_array($binding['kind'], ['class', 'alias'], true)) {
                $products = [...$products, ...$binding['concretes']];
            }
        }

        return \array_values(\array_unique($products));
    }

    /**
     * @param list<Implementation> $entries
     *
     * @return list<string>
     */
    private function products(string $abstract, array $entries): array
    {
        $products = [];

        foreach ($entries as $entry) {
            if (self::key($entry['abstract']) === self::key($abstract)) {
                $products = [...$products, ...$entry['concretes']];
            }
        }

        return \array_values(\array_unique($products));
    }
}
