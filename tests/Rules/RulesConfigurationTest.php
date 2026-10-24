<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class RulesConfigurationTest extends TestCase
{
    public function testLaravelSetChecksDeclarationsAndCallables(): void
    {
        $result = $this->analyse('laravel.neon');

        $this->assertSame(1, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('architecture.validationLocation', $result['output']);
        $this->assertStringContainsString('"file_errors":4', $result['output']);
    }

    public function testPhpSetDoesNotEnableLaravelRules(): void
    {
        $result = $this->analyse('php.neon');

        $this->assertSame(0, $result['exitCode'], $result['output']);
        $this->assertStringNotContainsString('architecture.validationLocation', $result['output']);
    }

    public function testLegacyConfigurationKeepsLaravelBehaviour(): void
    {
        $this->assertSame($this->analyse('laravel.neon'), $this->analyse('legacy.neon'));
    }

    public function testChecksAllConfiguredAnalysisPaths(): void
    {
        $result = $this->analyse('phpstan.neon');

        $this->assertSame(1, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('architecture.validationLocation', $result['output']);
        $this->assertStringContainsString('Outside.php', $result['output']);
        $this->assertStringContainsString('OutsideApp.php', $result['output']);
        $this->assertStringNotContainsString('Excluded.php', $result['output']);
        $this->assertStringNotContainsString('Allowed.php', $result['output']);
        $this->assertStringContainsString('"file_errors":2', $result['output']);
    }

    public function testAllowsConfiguredDirectoriesAndRejectsReplacedDefaults(): void
    {
        $result = $this->analyse('custom.neon');

        $this->assertSame(1, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('architecture.validationLocation', $result['output']);
        $this->assertStringContainsString('/app/Validation/Allowed.php', $result['output']);
        $this->assertStringNotContainsString('/custom/Validation/Allowed.php', $result['output']);
        $this->assertStringContainsString('/app/Providers/Allowed.php', $result['output']);
        $this->assertStringContainsString('/app/Rules/Allowed.php', $result['output']);
        $this->assertStringContainsString('/tests/Unit/Allowed.php', $result['output']);
        $this->assertStringContainsString('"file_errors":7', $result['output']);
    }

    public function testAnEmptyAllowlistForbidsValidationEverywhere(): void
    {
        $result = $this->analyse('empty.neon');

        $this->assertSame(1, $result['exitCode'], $result['output']);
        $this->assertStringContainsString('architecture.validationLocation', $result['output']);
        $this->assertStringContainsString('/app/Validation/Allowed.php', $result['output']);
        $this->assertStringContainsString('/app/Providers/Allowed.php', $result['output']);
        $this->assertStringContainsString('/app/Rules/Allowed.php', $result['output']);
        $this->assertStringContainsString('/tests/Unit/Allowed.php', $result['output']);
        $this->assertStringContainsString('"file_errors":7', $result['output']);
    }

    /**
     * @return array{exitCode: int|null, output: string}
     */
    private function analyse(string $configuration): array
    {
        $package = \dirname(__DIR__, 2);
        $process = new Process([
            \PHP_BINARY,
            $package . '/vendor/bin/phpstan',
            'analyse',
            '--configuration=' . $configuration,
            '--error-format=json',
            '--no-progress',
        ], $package . '/tests/Fixtures/consumer');
        $process->setTimeout(60);
        $process->run();

        return [
            'exitCode' => $process->getExitCode(),
            'output' => $process->getOutput() . $process->getErrorOutput(),
        ];
    }
}
