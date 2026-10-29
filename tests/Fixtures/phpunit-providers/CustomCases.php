<?php

declare(strict_types=1);

namespace ProviderFixtures\Custom;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

interface Effects
{
    public function record(): void;
}

function deliver(): void {}

function file_get_contents(string $path): string
{
    return $path;
}

final class EffectWriter implements Effects
{
    public function record(): void {}

    public function read(): int
    {
        return 1;
    }
}

final class CustomProviderTest extends TestCase
{
    #[DataProvider('customCases')]
    public function testCases(mixed $value): void {}

    public static function customCases(): array
    {
        $writer = new EffectWriter();
        $writer->record(); // @expect phpunit.providerSideEffect
        deliver(); // @expect phpunit.providerSideEffect

        return [[$writer->read(), file_get_contents('literal'), static fn () => $writer->record(), deliver(...)]];
    }
}
