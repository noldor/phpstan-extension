<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Rules\NoReferenceParameterRule;

/**
 * @extends RuleTestCase<NoReferenceParameterRule>
 */
final class NoReferenceParameterRuleTest extends RuleTestCase
{
    private bool $ownContract = false;

    public function testPreservesOnlyTheNativeStreamOpenReferenceParameter(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/CleanCode/StreamWrapperReferenceParameters.php'], [
            ['Return results instead of passing $path by reference in an owned API.', 25],
            ['Return results instead of passing $mode by reference in an owned API.', 25],
            ['Return results instead of passing $options by reference in an owned API.', 25],
            ['Return results instead of passing $extra by reference in an owned API.', 25],
            ['Return results instead of passing $value by reference in an owned API.', 30],
            ['Return results instead of passing $openedPath by reference in an owned API.', 35],
            ['Return results instead of passing $openedPath by reference in an owned API.', 43],
            ['Return results instead of passing $openedPath by reference in an owned API.', 49],
        ]);
    }

    public function testPreservesExternalVariadicReferenceRequirementsAtExpandedPositions(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/CleanCode/ExpandedReferenceParameters.php'], [
            ['Return results instead of passing $values by reference in an owned API.', 11],
        ]);
    }

    public function testOnlyPreservesRequiredExternalParentParameters(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/CleanCode/InheritedReferenceParameters.php'], [
            ['Return results instead of passing $value by reference in an owned API.', 11],
            ['Return results instead of passing $value by reference in an owned API.', 16],
        ]);
    }

    public function testRejectsOwnedReferenceParametersAndPreservesExternalRequirements(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/CleanCode/ReferenceParameters.php'], [
            ['Return results instead of passing $output by reference in an owned API.', 7],
            ['Return results instead of passing $count by reference in an owned API.', 14],
            ['Return results instead of passing $state by reference in an owned API.', 19],
            ['Return results instead of passing $values by reference in an owned API.', 24],
            ['Return results instead of passing $count by reference in an owned API.', 34],
            ['Return results instead of passing $count by reference in an owned API.', 39],
            ['Return results instead of passing $value by reference in an owned API.', 49],
            ['Return results instead of passing $flags by reference in an owned API.', 54],
        ]);
    }

    public function testTreatsAConfiguredContractFileAsOwned(): void
    {
        $this->ownContract = true;
        $this->analyse([__DIR__ . '/../Fixtures/CleanCode/ExternalReferenceContract.php'], [
            ['Return results instead of passing $value by reference in an owned API.', 9],
        ]);
    }

    public function testReportsAnErrorIdentifier(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/CleanCode/ReferenceParameters.php']);

        $this->assertNotEmpty($errors);
        $this->assertSame('api.referenceParameter', $errors[0]->getIdentifier());
    }

    protected function getRule(): NoReferenceParameterRule
    {
        return new NoReferenceParameterRule(self::getFileHelper(), [
            __DIR__ . '/../Fixtures/CleanCode/ReferenceParameters.php',
            __DIR__ . '/../Fixtures/CleanCode/StreamWrapperReferenceParameters.php',
            __DIR__ . '/../Fixtures/CleanCode/InheritedReferenceParameters.php',
            __DIR__ . '/../Fixtures/CleanCode/ExpandedReferenceParameters.php',
            ...($this->ownContract ? [__DIR__ . '/../Fixtures/CleanCode/ExternalReferenceContract.php'] : []),
        ]);
    }
}
