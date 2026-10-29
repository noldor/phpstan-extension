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

final class LaravelValidationSideEffectsExtension implements RestrictedFunctionUsageExtension, RestrictedMethodUsageExtension
{
    private readonly ForbiddenApis $apis;

    /**
     * @param list<array{class: string, methods: list<string>}> $forbiddenMethods
     * @param list<string> $forbiddenFunctions
     */
    public function __construct(
        private readonly ClassScope $validators,
        array $forbiddenMethods,
        array $forbiddenFunctions,
    ) {
        $this->apis = new ForbiddenApis($forbiddenMethods, $forbiddenFunctions);
    }

    public function isRestrictedMethodUsage(ExtendedMethodReflection $methodReflection, Scope $scope): RestrictedUsage|null
    {
        if (! $this->validators->contains($scope->getClassReflection()) || ! $this->apis->matchesMethod($methodReflection->getDeclaringClass(), $methodReflection->getName())) {
            return null;
        }

        return $this->restriction();
    }

    public function isRestrictedFunctionUsage(FunctionReflection $functionReflection, Scope $scope): RestrictedUsage|null
    {
        if (! $this->validators->contains($scope->getClassReflection()) || ! $this->apis->matchesFunction($functionReflection->getName())) {
            return null;
        }

        return $this->restriction();
    }

    private function restriction(): RestrictedUsage
    {
        return RestrictedUsage::create('Keep validation free of writes and delivery effects; perform this operation outside validation.', 'architecture.validationSideEffect');
    }
}
