<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\RestrictedUsage;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use Noldors\PHPStan\Configuration\ClassScope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension;

final class DtoInfrastructureUsageExtension implements RestrictedFunctionUsageExtension, RestrictedMethodUsageExtension
{
    private readonly ForbiddenApis $apis;

    /**
     * @param list<array{class: string, methods: list<string>}> $forbiddenMethods
     * @param list<string> $forbiddenFunctions
     */
    public function __construct(
        private readonly ClassScope $dtoScope,
        array $forbiddenMethods = [],
        array $forbiddenFunctions = [],
    ) {
        $this->apis = new ForbiddenApis($forbiddenMethods, $forbiddenFunctions);
    }

    public function isRestrictedMethodUsage(ExtendedMethodReflection $methodReflection, Scope $scope): RestrictedUsage|null
    {
        if (! $this->dtoScope->contains($scope->getClassReflection())) {
            return null;
        }

        if (! $this->apis->matchesMethod($methodReflection->getDeclaringClass(), $methodReflection->getName())) {
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

        if (! $this->apis->matchesFunction($functionReflection->getName())) {
            return null;
        }

        return $this->restriction(\sprintf('function %s()', $functionReflection->getName()));
    }

    private function restriction(string $operation): RestrictedUsage
    {
        return RestrictedUsage::create(
            \sprintf('Do not use infrastructure %s inside a DTO; perform infrastructure operations outside data-transfer objects.', $operation),
            'architecture.dtoInfrastructureUsage',
        );
    }
}
