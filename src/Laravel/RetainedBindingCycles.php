<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

/**
 * @phpstan-import-type BindingFact from RetainedBindingFacts
 */
final class RetainedBindingCycles
{
    public function __construct(
        private readonly RetainedResolverPolicy $policy,
    ) {}

    /**
     * @param array<string, list<BindingFact>> $bindings
     *
     * @return list<string>
     */
    public function find(string $name, array $bindings): array
    {
        $complete = [];

        return $this->visit($name, $bindings, [], $complete)['cycle'];
    }

    /**
     * @param array<string, list<BindingFact>> $bindings
     * @param array<string, string> $trail
     * @param array<string, true> $complete
     *
     * @return array{cycle: list<string>, complete: array<string, true>}
     */
    private function visit(string $name, array $bindings, array $trail, array $complete): array
    {
        $key = RetainedResolverPolicy::key($name);

        if (isset($trail[$key])) {
            return ['cycle' => [...\array_values($trail), $name], 'complete' => $complete];
        }

        if (isset($complete[$key])) {
            return ['cycle' => [], 'complete' => $complete];
        }
        $trail[$key] = $name;

        foreach ($bindings[$key] ?? [] as $binding) {
            if ('alias' === $binding['kind'] && \in_array($key, \array_map(RetainedResolverPolicy::key(...), $binding['concretes']), true)) {
                return ['cycle' => [...\array_values($trail), $name], 'complete' => $complete];
            }
        }

        foreach ($this->policy->resolutionProducts($name, $bindings[$key] ?? []) as $product) {
            if (RetainedResolverPolicy::key($product) === $key) {
                continue;
            }
            $result = $this->visit($product, $bindings, $trail, $complete);
            $complete = $result['complete'];

            if ([] !== $result['cycle']) {
                return $result;
            }
        }
        $complete[$key] = true;

        return ['cycle' => [], 'complete' => $complete];
    }
}
