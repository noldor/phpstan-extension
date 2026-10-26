<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Rules\PreservePreviousExceptionRule;

/**
 * @extends RuleTestCase<PreservePreviousExceptionRule>
 */
final class PreservePreviousExceptionRuleTest extends RuleTestCase
{
    public function testLeavesPotentialReferenceRebindingsAndUnprovenCauseVariablesUnchecked(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/CleanCode/PreviousExceptionReferenceCalls.php'], [[
            'Preserve the caught exception as the previous exception when translating it.',
            51,
        ]]);
    }

    public function testHandlesCustomCauseNamesWrappersAndReassignedVariables(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/CleanCode/PreviousExceptionBoundaries.php'], \array_map(
            static fn (int $line): array => ['Preserve the caught exception as the previous exception when translating it.', $line],
            [21, 33, 42],
        ));
    }

    public function testPreservesTheCaughtExceptionInTheActualConstructorParameter(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/CleanCode/PreviousExceptions.php'], \array_map(
            static fn (int $line): array => ['Preserve the caught exception as the previous exception when translating it.', $line],
            [37, 41, 57, 61, 91, 102],
        ));
    }

    public function testReportsAnErrorIdentifier(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/CleanCode/PreviousExceptions.php']);

        $this->assertNotEmpty($errors);
        $this->assertSame('exceptions.missingPrevious', $errors[0]->getIdentifier());
    }

    protected function getRule(): PreservePreviousExceptionRule
    {
        return new PreservePreviousExceptionRule(self::createReflectionProvider());
    }
}
