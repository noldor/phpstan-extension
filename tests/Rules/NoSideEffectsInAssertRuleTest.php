<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PDO;
use PDOStatement;
use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Rules\NoSideEffectsInAssertRule;

/**
 * @extends RuleTestCase<NoSideEffectsInAssertRule>
 */
final class NoSideEffectsInAssertRuleTest extends RuleTestCase
{
    public static function getAdditionalConfigFiles(): array
    {
        return [...parent::getAdditionalConfigFiles(), __DIR__ . '/../Fixtures/Assertions/visitor.neon'];
    }

    public function testRejectsEvaluatedAssignmentsAndConfiguredWrites(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/Assertions/Effects.php'], \array_map(
            static fn (int $line): array => ['Move side effects out of assert(); its arguments may not execute in production.', $line],
            [31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 48, 49, 50, 51, 52, 53, 56],
        ));
    }

    public function testPreservesInternalInvariantsDeferredBodiesAndUnresolvedTargets(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/Assertions/Accepted.php'], []);
    }

    public function testIgnoresVariableOuterCallsEvenWhenTheirTargetsAreReassigned(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/Assertions/DynamicOuter.php'], []);
    }

    public function testDefersGeneratorBodiesButStillChecksEagerArgumentsAndOrdinaryBodies(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/Assertions/Generators.php'], \array_map(
            static fn (int $line): array => ['Move side effects out of assert(); its arguments may not execute in production.', $line],
            [17, 19, 26, 29],
        ));
    }

    public function testReportsAnErrorIdentifier(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/Assertions/Effects.php']);

        $this->assertNotEmpty($errors);
        $this->assertSame('assert.sideEffect', $errors[0]->getIdentifier());
    }

    protected function getRule(): NoSideEffectsInAssertRule
    {
        return new NoSideEffectsInAssertRule(self::createReflectionProvider(), [
            ['class' => 'AssertFixtures\\Writer', 'methods' => ['save', 'write', '__construct']],
            ['class' => PDO::class, 'methods' => ['exec']],
            ['class' => PDOStatement::class, 'methods' => ['execute']],
        ], ['file_put_contents']);
    }
}
