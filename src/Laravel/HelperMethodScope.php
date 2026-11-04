<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\FunctionReflection;
use Noldors\PHPStan\Configuration\ClassScope;

final class HelperMethodScope
{
    /**
     * @var list<array{function: string, classes: ClassScope, methods: list<string>}>
     */
    private readonly array $allowances;

    /**
     * @param list<array{function: string, classes?: list<string>, namespaces?: list<string>, methods: list<string>}> $allowedHelpers
     */
    public function __construct(array $allowedHelpers)
    {
        $this->allowances = \array_map(static fn (array $entry): array => [
            'function' => \mb_strtolower(\mb_ltrim($entry['function'], '\\')),
            'classes' => new ClassScope($entry['classes'] ?? [], $entry['namespaces'] ?? []),
            'methods' => \array_map(\mb_strtolower(...), $entry['methods']),
        ], $allowedHelpers);
    }

    public function contains(FunctionReflection $function, Scope $scope): bool
    {
        $method = $scope->getFunction();

        if ($scope->isInAnonymousFunction() || ! $method instanceof MethodReflection) {
            return false;
        }
        $name = \mb_strtolower(\mb_ltrim($function->getName(), '\\'));

        foreach ($this->allowances as $allowance) {
            if ($name === $allowance['function']
                && \in_array(\mb_strtolower($method->getName()), $allowance['methods'], true)
                && $allowance['classes']->contains($scope->getClassReflection())) {
                return true;
            }
        }

        return false;
    }
}
