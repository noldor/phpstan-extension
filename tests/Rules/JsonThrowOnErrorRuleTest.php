<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Rules\JsonThrowOnErrorRule;

/**
 * @extends RuleTestCase<JsonThrowOnErrorRule>
 */
final class JsonThrowOnErrorRuleTest extends RuleTestCase
{
    public function testRequiresTheThrowFlagOnResolvedNativeJsonCalls(): void
    {
        $encodeMessage = 'Pass JSON_THROW_ON_ERROR in the flags argument of json_encode(); preserve other required flags.';
        $decodeMessage = 'Pass JSON_THROW_ON_ERROR in the flags argument of json_decode(); preserve other required flags.';

        $this->analyse([__DIR__ . '/../Fixtures/JsonThrowOnError/Calls.php'], [
            [$encodeMessage, 19],
            [$encodeMessage, 20],
            [$encodeMessage, 21],
            [$encodeMessage, 22],
            [$encodeMessage, 23],
            [$decodeMessage, 24],
            [$decodeMessage, 25],
            [$decodeMessage, 26],
            [$decodeMessage, 27],
            [$decodeMessage, 28],
            [$decodeMessage, 29],
            [$encodeMessage, 30],
            [$decodeMessage, 31],
            [$encodeMessage, 32],
            [$encodeMessage, 33],
            [$encodeMessage, 35],
            [$decodeMessage, 37],
            [$encodeMessage, 38],
            [$encodeMessage, 78],
        ]);
    }

    public function testReportsTheJsonErrorIdentifier(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/JsonThrowOnError/Calls.php']);

        $this->assertNotEmpty($errors);
        $this->assertSame('json.missingThrowOnError', $errors[0]->getIdentifier());
    }

    protected function getRule(): JsonThrowOnErrorRule
    {
        return new JsonThrowOnErrorRule(self::createReflectionProvider());
    }
}
