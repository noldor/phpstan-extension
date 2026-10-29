<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Rules\LaravelEnumRule;
use Noldors\PHPStan\Configuration\ClassScope;

/**
 * @extends RuleTestCase<LaravelEnumRule>
 */
final class LaravelEnumRuleTest extends RuleTestCase
{
    public function testChecksNamesCasesAndConfiguredDomainDirectories(): void
    {
        $errors = $this->gatherAnalyserErrors([
            __DIR__ . '/../Fixtures/standards/enums/Orders/StatusEnum.php',
            __DIR__ . '/../Fixtures/standards/OutsideEnum.php',
        ]);
        $actual = \array_map(static fn ($error): string => $error->getIdentifier() ?? 'missing.identifier', $errors);
        \sort($actual);
        $this->assertSame(['laravel.enumCase', 'laravel.enumLocation', 'laravel.enumName'], $actual);
    }

    protected function getRule(): LaravelEnumRule
    {
        return new LaravelEnumRule(
            new ClassScope(namespaces: ['StandardsFixture\\Enums']),
            self::getFileHelper(),
            [__DIR__ . '/../Fixtures/standards/enums'],
        );
    }
}
