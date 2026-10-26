<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\RestrictedUsage;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionReflection;
use Noldors\PHPStan\Configuration\ClassScope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension;

final class DtoInfrastructureUsageExtension implements RestrictedFunctionUsageExtension, RestrictedMethodUsageExtension
{
    /**
     * @param list<array{class: string, methods: list<string>}> $forbiddenMethods
     * @param list<string> $forbiddenFunctions
     */
    public function __construct(
        private readonly ClassScope $dtoScope,
        private readonly array $forbiddenMethods = [],
        private readonly array $forbiddenFunctions = [],
    ) {}

    public function isRestrictedMethodUsage(ExtendedMethodReflection $methodReflection, Scope $scope): RestrictedUsage|null
    {
        if (! $this->dtoScope->contains($scope->getClassReflection())) {
            return null;
        }

        if (! $this->isForbiddenMethod($methodReflection->getDeclaringClass(), $methodReflection->getName())) {
            return null;
        }

        return $this->restriction(\sprintf(
            'method %s::%s()',
            $methodReflection->getDeclaringClass()->getDisplayName(),
            $methodReflection->getName(),
        ));
    }

    public function isRestrictedFunctionUsage(FunctionReflection $functionReflection, Scope $scope): RestrictedUsage|null
    {
        if (! $this->dtoScope->contains($scope->getClassReflection())) {
            return null;
        }

        foreach ($this->forbiddenFunctions as $function) {
            if (\strcasecmp(\mb_ltrim($function, '\\'), $functionReflection->getName()) === 0) {
                return $this->restriction(\sprintf('function %s()', $functionReflection->getName()));
            }
        }

        return null;
    }

    private function isForbiddenMethod(ClassReflection $class, string $method): bool
    {
        foreach ($this->forbiddenMethods as $forbidden) {
            $targetClass = \mb_ltrim($forbidden['class'], '\\');

            if (\strcasecmp($class->getName(), $targetClass) !== 0 && ! $class->is($targetClass)) {
                continue;
            }

            foreach ($forbidden['methods'] as $forbiddenMethod) {
                if ('*' === $forbiddenMethod || \strcasecmp($forbiddenMethod, $method) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    private function restriction(string $operation): RestrictedUsage
    {
        return RestrictedUsage::create(
            \sprintf('Do not use infrastructure %s inside a DTO; perform infrastructure operations outside data-transfer objects.', $operation),
            'architecture.dtoInfrastructureUsage',
        );
    }
}
