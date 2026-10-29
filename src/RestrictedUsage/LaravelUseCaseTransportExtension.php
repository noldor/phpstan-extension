<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\RestrictedUsage;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Rules\ClassNameUsageLocation;
use Noldors\PHPStan\Configuration\ClassScope;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use PHPStan\Rules\RestrictedUsage\RestrictedClassNameUsageExtension;

final class LaravelUseCaseTransportExtension implements RestrictedClassNameUsageExtension
{
    /**
     * @param list<string> $transportTypes
     */
    public function __construct(
        private readonly ClassScope $useCases,
        private readonly array $transportTypes,
    ) {}

    public function isRestrictedClassNameUsage(ClassReflection $classReflection, Scope $scope, ClassNameUsageLocation $location): RestrictedUsage|null
    {
        if (! $this->useCases->contains($scope->getClassReflection())) {
            return null;
        }

        foreach ($this->transportTypes as $type) {
            if ($classReflection->is(\mb_ltrim($type, '\\'))) {
                return RestrictedUsage::create($location->createMessage('HTTP/GraphQL transport type ' . $classReflection->getDisplayName()) . ' Pass operation data and the actor explicitly.', 'architecture.useCaseTransport');
            }
        }

        return null;
    }
}
