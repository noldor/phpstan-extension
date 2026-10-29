<?php

declare(strict_types=1);

namespace ProviderFixtures;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

abstract class InheritedProviderTest extends TestCase
{
    #[DataProvider('inheritedCases')]
    public function testInherited(mixed $value): void {}

    public static function inheritedCases(): array
    {
        return [[1]];
    }
}

final class InheritedConcreteTest extends InheritedProviderTest
{
    public static function inheritedCases(): array
    {
        return [[\file_get_contents('/not-executed/inherited.txt')]]; // @expect phpunit.providerSideEffect
    }
}

abstract class ProviderMethodBase extends TestCase
{
    public static function sharedCases(): array
    {
        return [[\file_get_contents('/not-executed/shared.txt')]]; // @expect phpunit.providerSideEffect
    }
}

final class ConsumerOfInheritedProviderTest extends ProviderMethodBase
{
    #[DataProvider('sharedCases')]
    public function testInheritedProvider(mixed $value): void {}
}
