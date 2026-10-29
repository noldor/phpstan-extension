<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Rules\FullRequestLoggingRule;

/**
 * @extends RuleTestCase<FullRequestLoggingRule>
 */
final class FullRequestLoggingRuleTest extends RuleTestCase
{
    public function testRejectsDirectRequestPayloadsOnlyAtKnownLoggers(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/FullRequestLogging/Calls.php']);
        $this->assertSame([12, 13, 14, 15, 16, 17, 18], \array_map(static fn ($error): int|null => $error->getLine(), $errors));

        foreach ($errors as $error) {
            $this->assertSame('logging.fullRequestPayload', $error->getIdentifier());
        }
    }

    public function testRecognizesFullLaravelSelectionsAndAcceptsOrdinaryNullContext(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/FullRequestLogging/Selections.php']);
        $this->assertSame([10, 11, 12, 13, 14, 15, 16], \array_map(static fn ($error): int|null => $error->getLine(), $errors));
    }

    protected function getRule(): FullRequestLoggingRule
    {
        return new FullRequestLoggingRule(self::createReflectionProvider(), self::getContainer()->getByType(\PHPStan\File\FileHelper::class));
    }
}
