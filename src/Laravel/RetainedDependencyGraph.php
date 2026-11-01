<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;

/**
 * @phpstan-import-type BindingFact from RetainedBindingFacts
 *
 * @phpstan-type GraphProblem array{identifier: string, message: string}
 * @phpstan-type ClassEdges array{classes: list<ClassReflection>, reason: string|null}
 * @phpstan-type GraphStep array{classes: list<string|ClassReflection>, constructed?: list<string>, reason: string|null, unsafe: bool}
 */
final class RetainedDependencyGraph
{
    public function __construct(
        private readonly RetainedResolverPolicy $policy,
        private readonly ReflectionProvider $reflection,
        private readonly RetainedConstructorEdges $constructors,
        private readonly RetainedPropertyEdges $properties,
        private readonly RetainedBindingCycles $cycles,
    ) {}

    /**
     * @param array<string, list<BindingFact>> $bindings
     *
     * @return GraphProblem|null
     */
    public function check(string $root, array $bindings): array|null
    {
        $pending = [[$root, [$root], false, []]];
        $visited = [];

        for ($index = 0; \count($pending) > $index; ++$index) {
            [$dependency, $chain, $reviewedConstruction, $ancestors] = $pending[$index];
            $name = $dependency instanceof ClassReflection ? $dependency->getName() : $dependency;
            $typeKey = $this->typeKey($dependency);
            $nameKey = RetainedResolverPolicy::key($name);

            if (isset($ancestors[$nameKey]) && $ancestors[$nameKey] !== $typeKey) {
                return $this->problem($chain, 'recursive generic specialization requires an explicit lifetime boundary', false);
            }
            $ancestors[$nameKey] = $typeKey;
            $key = $typeKey . ($reviewedConstruction ? ':reviewed' : ':autowired');

            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;
            $step = $this->step($dependency, $bindings, $reviewedConstruction);

            if (null !== $step['reason']) {
                return $this->problem($chain, $step['reason'], $step['unsafe']);
            }
            $cycle = $reviewedConstruction ? [] : $this->cycles->find($name, $bindings);

            if ([] !== $cycle) {
                return $this->problem([...$chain, ...\array_slice($cycle, 1)], 'cyclic container bindings have no concrete construction boundary', false);
            }
            $children = \array_map(static fn (ClassReflection|string $child): array => [$child, false], $step['classes']);
            $children = [...$children, ...\array_map(static fn (string $child): array => [$child, true], $step['constructed'] ?? [])];
            \usort($children, fn (array $left, array $right): int => \strcmp($this->display($left[0]), $this->display($right[0])) ?: ($left[1] <=> $right[1]));

            foreach ($children as [$child, $constructed]) {
                $pending[] = [$child, [...$chain, $this->display($child)], $constructed, $ancestors];
            }
        }

        return null;
    }

    /**
     * @param array<string, list<BindingFact>> $bindings
     *
     * @return GraphStep
     */
    private function step(ClassReflection|string $dependency, array $bindings, bool $reviewedConstruction): array
    {
        if (\is_string($dependency) && ! $this->reflection->hasClass($dependency)) {
            return $this->containerKeyStep($dependency, $bindings[RetainedResolverPolicy::key($dependency)] ?? []);
        }
        $class = $dependency instanceof ClassReflection ? $dependency : $this->reflection->getClass($dependency);
        $unsafe = $this->unsafeType($class);

        if (null !== $unsafe) {
            return ['classes' => [], 'reason' => 'request/operation-bound type ' . $unsafe, 'unsafe' => true];
        }

        if ($reviewedConstruction) {
            return $this->concreteStep($class, [], true);
        }
        $bindings = $this->bindingFacts($class, $bindings);
        $reviewed = $this->policy->reviewedProducts($class->getName());
        $reason = $this->constructionProblem($class, $bindings, [] !== $reviewed);

        if (null !== $reason) {
            return ['classes' => [], 'reason' => $reason, 'unsafe' => false];
        }
        $products = $this->policy->resolutionProducts($class->getName(), $bindings);
        $otherProducts = \array_values(\array_filter($products, static fn (string $product): bool => RetainedResolverPolicy::key($product) !== RetainedResolverPolicy::key($class->getName())));

        $constructed = \array_values(\array_filter($reviewed, static fn (string $product): bool => RetainedResolverPolicy::key($product) !== RetainedResolverPolicy::key($class->getName())));
        $allProducts = [...$products, ...$reviewed];

        if ([] !== $allProducts && ! \in_array(RetainedResolverPolicy::key($class->getName()), \array_map(RetainedResolverPolicy::key(...), $allProducts), true)) {
            return ['classes' => $otherProducts, 'constructed' => $constructed, 'reason' => $class->isGeneric() ? 'generic implementation mapping has no specialized product types' : null, 'unsafe' => false];
        }
        $step = $this->concreteStep($class, $otherProducts, [] !== $reviewed);
        $step['constructed'] = $constructed;

        return $step;
    }

    /**
     * @param array<string, list<BindingFact>> $bindings
     *
     * @return list<BindingFact>
     */
    private function bindingFacts(ClassReflection $class, array $bindings): array
    {
        $key = RetainedResolverPolicy::key($class->getName());
        $facts = \array_values(\array_filter($bindings[$key] ?? [], static fn (array $fact): bool => ! ($fact['inheritedOnly'] ?? false)));

        foreach ($bindings as $abstract => $candidates) {
            if ($abstract === $key || ! $class->is($abstract)) {
                continue;
            }

            foreach ($candidates as $candidate) {
                if ($candidate['inherited'] ?? false) {
                    $facts[] = $candidate;
                }
            }
        }

        return $facts;
    }

    /**
     * @param list<string> $otherProducts
     *
     * @return GraphStep
     */
    private function concreteStep(ClassReflection $class, array $otherProducts, bool $reviewedConstruction): array
    {
        if ($class->isInterface() || $class->isAbstract() || $class->isTrait()) {
            return ['classes' => [], 'reason' => 'no finite concrete implementation is known', 'unsafe' => false];
        }

        if ($this->policy->isSafeBoundary($class->getName()) || $class->isEnum()) {
            return ['classes' => $otherProducts, 'reason' => null, 'unsafe' => false];
        }
        $construction = $this->constructors->forClass($class, $reviewedConstruction);
        $properties = $this->properties->forClass($class);

        return ['classes' => [...$otherProducts, ...$construction['classes'], ...$properties['classes']], 'reason' => $construction['reason'] ?? $properties['reason'], 'unsafe' => false];
    }

    /**
     * @param list<BindingFact> $bindings
     */
    private function constructionProblem(ClassReflection $class, array $bindings, bool $reviewedConstruction): string|null
    {
        if (! $reviewedConstruction && $this->constructors->hasCustomConstruction($class)) {
            return 'construction attributes or self-building behavior require a reviewed finite factory declaration';
        }

        foreach ($bindings as $binding) {
            if (! \in_array($binding['kind'], ['class', 'alias'], true) && [] === $this->policy->reviewedProducts($class->getName())) {
                return $binding['reason'] . ' requires a reviewed finite factory declaration';
            }
        }

        return null;
    }

    /**
     * @param list<BindingFact> $bindings
     *
     * @return GraphStep
     */
    private function containerKeyStep(string $name, array $bindings): array
    {
        foreach ($bindings as $binding) {
            if ($binding['inheritedOnly'] ?? false) {
                continue;
            }

            if (! \in_array($binding['kind'], ['class', 'alias'], true) && [] === $this->policy->reviewedProducts($name)) {
                return ['classes' => [], 'reason' => $binding['reason'] . ' requires a reviewed finite factory declaration', 'unsafe' => false];
            }
        }
        $products = \array_values(\array_filter($this->policy->resolutionProducts($name, $bindings), static fn (string $product): bool => RetainedResolverPolicy::key($product) !== RetainedResolverPolicy::key($name)));

        $constructed = $this->policy->reviewedProducts($name);

        return ['classes' => $products, 'constructed' => $constructed, 'reason' => [] === $products && [] === $constructed ? 'class or container key has no finite reflected construction' : null, 'unsafe' => false];
    }

    private function unsafeType(ClassReflection $class): string|null
    {
        foreach ($this->policy->unsafeClasses as $unsafe) {
            if ($class->is($unsafe)) {
                return $unsafe;
            }
        }

        return null;
    }

    private function display(ClassReflection|string $dependency): string
    {
        return $dependency instanceof ClassReflection ? $dependency->getDisplayName() : $dependency;
    }

    private function typeKey(ClassReflection|string $dependency): string
    {
        if (\is_string($dependency)) {
            return RetainedResolverPolicy::key($dependency) . ':[]';
        }
        $types = \array_map(static fn (Type $type): string => $type->describe(VerbosityLevel::precise()), $dependency->getActiveTemplateTypeMap()->getTypes());

        return RetainedResolverPolicy::key($dependency->getName()) . ':' . \json_encode($types, \JSON_THROW_ON_ERROR);
    }

    /**
     * @param list<string> $chain
     *
     * @return GraphProblem
     */
    private function problem(array $chain, string $reason, bool $unsafe): array
    {
        return ['identifier' => $unsafe ? 'lighthouse.retainedUnsafeDependency' : 'lighthouse.retainedUnknownDependency', 'message' => \sprintf('Retained resolver dependency %s: %s. Pass invocation state explicitly; scoped/readonly do not refresh retained objects.', \implode(' -> ', $chain), $reason)];
    }
}
