<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\RestrictedUsage;

use PHPStan\Analyser\Scope;
use Illuminate\Support\Facades\Facade;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use Noldors\PHPStan\Laravel\LaravelHelperFunctions;
use Noldors\PHPStan\Laravel\DependencyInjectionScope;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;

final class LaravelDependencyAccessExtension implements RestrictedClassNameUsageExtension, RestrictedFunctionUsageExtension, RestrictedMethodUsageExtension
{
    private readonly ForbiddenApis $serviceLocators;

    /**
     * @param list<string> $helpers
     * @param list<string> $serviceLocatorTypes
     * @param list<string> $serviceLocatorMethods
     */
    public function __construct(
        private readonly DependencyInjectionScope $selection,
        private readonly array $helpers,
        private readonly bool $allLaravelHelpers,
        array $serviceLocatorTypes,
        array $serviceLocatorMethods,
    ) {
        $this->serviceLocators = new ForbiddenApis(\array_map(static fn (string $type): array => ['class' => $type, 'methods' => $serviceLocatorMethods], $serviceLocatorTypes), []);
    }

    public function isRestrictedClassNameUsage(ClassReflection $classReflection, Scope $scope, ClassNameUsageLocation $location): RestrictedUsage|null
    {
        return $this->selection->contains($scope) && $classReflection->is(Facade::class) ? $this->restriction() : null;
    }

    public function isRestrictedFunctionUsage(FunctionReflection $functionReflection, Scope $scope): RestrictedUsage|null
    {
        return $this->selection->contains($scope) && LaravelHelperFunctions::matches($functionReflection, $this->allLaravelHelpers, $this->helpers) ? $this->restriction() : null;
    }

    public function isRestrictedMethodUsage(ExtendedMethodReflection $methodReflection, Scope $scope): RestrictedUsage|null
    {
        return $this->selection->contains($scope) && $this->serviceLocators->matchesMethod($methodReflection->getDeclaringClass(), $methodReflection->getName()) ? $this->restriction() : null;
    }

    private function restriction(): RestrictedUsage
    {
        return RestrictedUsage::create('Inject dependencies in classes that support dependency injection; do not use Laravel facades, global helpers or service locators here.', 'architecture.dependencyInjection');
    }
}
