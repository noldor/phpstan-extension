<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

/**
 * @phpstan-import-type BindingFact from RetainedBindingFacts
 */
final class RetainedBindingAliases
{
    /**
     * @param array<string, list<BindingFact>> $bindings
     *
     * @return array<string, list<BindingFact>>
     */
    public function normalize(array $bindings): array
    {
        $normalized = [];

        foreach ($bindings as $abstract => $registrations) {
            foreach ($registrations as $registration) {
                $targets = ($registration['canonical'] ?? false) ? $this->targets($abstract, $bindings) : [$abstract];

                foreach ($targets as $target) {
                    $normalized[$target][] = $registration;
                }

                if (($registration['inherited'] ?? false) && ! \in_array($abstract, $targets, true)) {
                    $registration['inheritedOnly'] = true;
                    $normalized[$abstract][] = $registration;
                }
            }
        }

        return $normalized;
    }

    /**
     * @param array<string, list<BindingFact>> $bindings
     *
     * @return list<string>
     */
    private function targets(string $abstract, array $bindings): array
    {
        $pending = [$abstract];
        $visited = [];
        $targets = [];

        for ($index = 0; \count($pending) > $index; ++$index) {
            $key = RetainedResolverPolicy::key($pending[$index]);

            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;

            $aliases = [];

            foreach ($bindings[$key] ?? [] as $registration) {
                if ('alias' === $registration['kind']) {
                    $aliases = [...$aliases, ...$registration['concretes']];
                }
            }

            if ([] === $aliases) {
                $targets[$key] = true;
            }
            $pending = [...$pending, ...$aliases];
        }

        return [] !== $targets ? \array_keys($targets) : [$abstract];
    }
}
