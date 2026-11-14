<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\RestrictedUsage;

use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Rules\RestrictedUsage\RestrictedUsage;
use PHPStan\Rules\RestrictedUsage\RestrictedFunctionUsageExtension;

final class ParseUrlUsageExtension implements RestrictedFunctionUsageExtension
{
    public function isRestrictedFunctionUsage(FunctionReflection $functionReflection, Scope $scope): RestrictedUsage|null
    {
        if ('parse_url' !== \mb_strtolower($functionReflection->getName())) {
            return null;
        }

        return RestrictedUsage::create('Use Uri\\Rfc3986\\Uri instead of parse_url().', 'uri.parseUrl');
    }
}
