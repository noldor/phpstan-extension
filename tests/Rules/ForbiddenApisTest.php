<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\PHPStanTestCase;
use Noldors\PHPStan\RestrictedUsage\ForbiddenApis;

final class ForbiddenApisTest extends PHPStanTestCase
{
    public function testTraitSelectorsIgnoreCaseAndIncludeInheritedNestedTraits(): void
    {
        $class = self::createReflectionProvider()->getClass(ApiOwner::class);
        $apis = new ForbiddenApis([['class' => '\\' . \mb_strtolower(EffectApi::class), 'methods' => ['WRITE']]], []);

        $this->assertTrue($apis->matchesMethod($class, 'write'));
        $this->assertFalse($apis->matchesMethod($class, 'read'));
    }
}

trait EffectApi
{
    public function write(): void {}
}

trait WrappedApi
{
    use EffectApi;
}

abstract class BaseApiOwner
{
    use WrappedApi;
}

final class ApiOwner extends BaseApiOwner {}
