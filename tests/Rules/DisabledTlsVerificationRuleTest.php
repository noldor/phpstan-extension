<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Rules\DisabledTlsVerificationRule;

/**
 * @extends RuleTestCase<DisabledTlsVerificationRule>
 */
final class DisabledTlsVerificationRuleTest extends RuleTestCase
{
    public function testReportsExplicitInsecureOptionsAndPreservesTrustedCaAndUnknownOptions(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/DisabledTls/Calls.php']);
        $this->assertSame([11, 12, 13, 14, 15, 16, 17, 18, 28, 29, 30, 31, 32], \array_map(static fn ($error): int|null => $error->getLine(), $errors));

        foreach ($errors as $error) {
            $this->assertSame('security.disabledTlsVerification', $error->getIdentifier());
        }
    }

    public function testCanExcludeExplicitTestFiles(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/DisabledTls/Calls.php'], []);
    }

    public function testNativeSslOptionsUseBooleanConversionWithoutChangingCaPathSemantics(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/DisabledTls/NativeBooleanOptions.php']);
        $this->assertSame([7, 8, 9, 10], \array_map(static fn ($error): int|null => $error->getLine(), $errors));
    }

    public function testCurlFlagsUseIntegerConversionDistinctFromStreamAndGuzzleOptions(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/DisabledTls/CurlIntegerOptions.php']);
        $this->assertSame([7, 8, 9, 10], \array_map(static fn ($error): int|null => $error->getLine(), $errors));
    }

    protected function getRule(): DisabledTlsVerificationRule
    {
        return new DisabledTlsVerificationRule(
            self::createReflectionProvider(),
            self::getContainer()->getByType(\PHPStan\File\FileHelper::class),
            $this->name() === 'testCanExcludeExplicitTestFiles' ? [\dirname(__DIR__) . '/Fixtures/DisabledTls'] : [],
            laravelClients: true,
        );
    }
}
