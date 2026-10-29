<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Rules\LaravelPostgresInstantRule;

/**
 * @extends RuleTestCase<LaravelPostgresInstantRule>
 */
final class LaravelPostgresInstantRuleTest extends RuleTestCase
{
    private const string MESSAGE = 'Use timestamp(6) with time zone for application-owned PostgreSQL instants; declare timestampTz(..., 6), timestampsTz(6) or softDeletesTz(..., 6).';

    public function testRejectsNaiveTimestampsAndUnprovenMicrosecondPrecision(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/PostgresInstants/Invalid.php'], \array_map(
            static fn (int $line): array => [self::MESSAGE, $line],
            [...\range(14, 40), 42, 44],
        ));
    }

    public function testAcceptsExplicitTimezoneAndPrecisionAndOtherTemporalSemantics(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/PostgresInstants/Valid.php'], []);
    }

    public function testChecksGenericColumnDeclarations(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/PostgresInstants/AddColumn.php'], \array_map(
            static fn (int $line): array => [self::MESSAGE, $line],
            \range(14, 19),
        ));
    }

    public function testReportsAStableIdentifier(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/PostgresInstants/AddColumn.php']);

        $this->assertCount(6, $errors);
        $this->assertSame('laravel.postgresqlInstant', $errors[0]->getIdentifier());
    }

    public function testChecksEffectiveGenericTypesWithLaravelCaseInsensitiveDispatch(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/PostgresInstants/GenericBoundaries.php'], \array_map(
            static fn (int $line): array => [self::MESSAGE, $line],
            \range(11, 15),
        ));
    }

    public function testChecksNullsafeDeclarationsWithoutDuplicateDiagnostics(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/PostgresInstants/Nullsafe.php'], [
            [self::MESSAGE, 11],
            [self::MESSAGE, 12],
        ]);
    }

    public function testKeepsGenericTypeAndPrecisionAlternativesCorrelated(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/PostgresInstants/GenericAlternatives.php'], [[self::MESSAGE, 16]]);
    }

    protected function getRule(): LaravelPostgresInstantRule
    {
        return new LaravelPostgresInstantRule(
            self::getFileHelper(),
            [__DIR__ . '/../Fixtures/PostgresInstants'],
            [],
        );
    }
}
