<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\RestrictedUsage;

use PHPStan\Analyser\Scope;
use Noldors\PHPStan\Configuration\ClassScope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension;

final class LaravelUseCaseValidationExtension implements RestrictedMethodUsageExtension
{
    private readonly ForbiddenApis $apis;

    /**
     * @param list<string> $validationExceptions
     */
    public function __construct(
        private readonly ClassScope $useCases,
        array $validationExceptions,
    ) {
        $selectors = [['class' => \Illuminate\Contracts\Validation\Validator::class, 'methods' => ['validate', 'validateWithBag']]];

        foreach ($validationExceptions as $exception) {
            $selectors[] = ['class' => $exception, 'methods' => ['withMessages']];
        }
        $this->apis = new ForbiddenApis($selectors, []);
    }

    public function isRestrictedMethodUsage(ExtendedMethodReflection $methodReflection, Scope $scope): RestrictedUsage|null
    {
        if (! $this->useCases->contains($scope->getClassReflection()) || ! $this->apis->matchesMethod($methodReflection->getDeclaringClass(), $methodReflection->getName())) {
            return null;
        }

        return RestrictedUsage::create('Keep validation errors at the entry-point boundary; use a domain exception inside a use case.', 'architecture.useCaseValidation');
    }
}
