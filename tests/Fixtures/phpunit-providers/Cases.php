<?php

declare(strict_types=1);

namespace ProviderFixtures;

use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider as Dataset;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\TestCase;

final readonly class ImmutableCase
{
    public function __construct(public int $value) {}
}

final class ProviderTest extends TestCase
{
    private const FILE_PROVIDER = 'readFiles';

    #[Dataset(self::FILE_PROVIDER)]
    #[Dataset('READFILES')]
    #[Dataset('database')]
    #[Dataset('doubles')]
    #[Dataset('immediateClosures')]
    #[Dataset('values')]
    #[DataProviderExternal(ExternalProviders::class, 'records')]
    #[DataProviderExternal('ProviderFixtures\\ExternalProviders', 'records')]
    public function testOperation(mixed $value): void {}

    public static function readFiles(): array
    {
        $read = file_get_contents(...);
        $text = \file_get_contents('/not-executed/provider.txt'); // @expect phpunit.providerSideEffect
        \file_put_contents('/not-executed/provider.txt', 'fixture'); // @expect phpunit.providerSideEffect

        return [[$text, $read]];
    }

    public static function database(): array
    {
        $database = new PDO('sqlite::memory:'); // @expect phpunit.providerSideEffect
        $statement = $database->query('SELECT 1'); // @expect phpunit.providerSideEffect

        if (false !== $statement) {
            $statement->execute(); // @expect phpunit.providerSideEffect
        }

        return [[1]];
    }

    public static function doubles(): array
    {
        $test = new self('testOperation');
        $test->getMockBuilder(\Stringable::class); // @expect phpunit.providerSideEffect
        $test->createMock(\Stringable::class); // @expect phpunit.providerSideEffect
        $test->createPartialMock(\stdClass::class, []); // @expect phpunit.providerSideEffect
        $test->createMockForIntersectionOfInterfaces([\Stringable::class, \Countable::class]); // @expect phpunit.providerSideEffect
        self::createStub(\Stringable::class); // @expect phpunit.providerSideEffect
        self::createStubForIntersectionOfInterfaces([\Stringable::class, \Countable::class]); // @expect phpunit.providerSideEffect
        (new \PHPUnit\Framework\MockObject\MockBuilder($test, \Stringable::class))->getMock(); // @expect phpunit.providerSideEffect
        (new \PHPUnit\Framework\MockObject\TestStubBuilder(\Stringable::class))->getStub(); // @expect phpunit.providerSideEffect

        return [[1]];
    }

    public static function immediateClosures(): array
    {
        $text = (static fn (): string|false => \file_get_contents('/not-executed/immediate.txt'))(); // @expect phpunit.providerSideEffect
        $other = (static function (): string|false {
            return \file_get_contents('/not-executed/immediate.txt'); // @expect phpunit.providerSideEffect
        })();
        $nestedGenerator = (static function (): array {
            $deferred = static function (): \Generator {
                yield \file_get_contents('/not-executed/nested-generator.txt');
            };
            \file_put_contents('/not-executed/eager-outer.txt', 'fixture'); // @expect phpunit.providerSideEffect

            return [$deferred];
        })();

        return [[$text, $other, $nestedGenerator]];
    }

    public static function values(): iterable
    {
        yield 'scalars' => [1, null, ['enabled' => true]];
        yield 'immutable objects' => [new DateTimeImmutable('2020-01-01'), new ImmutableCase(42)];
        yield 'deferred factories' => [static fn () => self::createStub(\Stringable::class)];
        yield 'deferred IO' => [static function (): string|false {
            return \file_get_contents('/not-executed/deferred.txt');
        }];
        yield 'callable reference' => [\file_get_contents(...)];
        yield 'deferred nested IIFE' => [static fn () => (static fn () => \file_get_contents('/not-executed/nested.txt'))()];
        yield 'called generator closure' => [(static function (): \Generator {
            yield \file_get_contents('/not-executed/generator-body.txt');
        })()];
        yield 'called generator arrow' => [(static fn (): \Generator => yield \file_get_contents('/not-executed/generator-arrow.txt'))()];
        yield 'called yield-from closure' => [(static function (): \Generator {
            yield from [\file_get_contents('/not-executed/yield-from-body.txt')];
        })()];
        yield 'generator call with eager argument' => [(static function (mixed $value): \Generator {
            yield \file_get_contents('/not-executed/generator-with-argument.txt');
        })(\file_get_contents('/not-executed/eager-argument.txt'))]; // @expect phpunit.providerSideEffect
        yield 'generator arrow with eager argument' => [(static fn (mixed $value): \Generator => yield \file_get_contents('/not-executed/generator-arrow-body.txt'))(
            \file_get_contents('/not-executed/eager-arrow-argument.txt'), // @expect phpunit.providerSideEffect
        )];
        yield 'provider iteration executes IO' => [\file_get_contents('/not-executed/provider-generator.txt')]; // @expect phpunit.providerSideEffect
        yield 'anonymous object' => [new class {
            public function later(): string|false
            {
                return \file_get_contents('/not-executed/object.txt');
            }
        }];
    }

    public static function fixtureFactory(): array
    {
        return [[\file_get_contents('/not-executed/unregistered.txt')]];
    }

    public function testEffectsAfterSetup(): void
    {
        \file_get_contents('/not-executed/test.txt');
        self::createStub(\Stringable::class);
    }
}

final class LooksLikeProvider
{
    #[Dataset('dataProvider')]
    public function run(): void {}

    public static function dataProvider(): array
    {
        return [[\file_get_contents('/not-executed/unrelated.txt')]];
    }
}
