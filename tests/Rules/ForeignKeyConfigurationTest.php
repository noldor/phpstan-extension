<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ForeignKeyConfigurationTest extends TestCase
{
    public function testLaravelSetRegistersTheScopedMigrationCheck(): void
    {
        $process = $this->analyse('foreign-keys-laravel.neon');
        $output = $process->getOutput() . $process->getErrorOutput();
        $this->assertSame(1, $process->getExitCode(), $output);
        $this->assertStringContainsString('laravel.foreignKeyTable', $output);
        $this->assertStringContainsString('"file_errors":3', $output);
    }

    public function testRegisteredRuleChecksOnlyConfiguredMigrationPaths(): void
    {
        $process = $this->analyse('foreign-keys.neon');
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertSame(1, $process->getExitCode(), $output);
        $this->assertStringContainsString('laravel.foreignKeyTable', $output);
        $this->assertStringContainsString('laravel.foreignKeyDelete', $output);
        $this->assertStringContainsString('laravel.foreignKeyUpdate', $output);
        $this->assertStringContainsString('"file_errors":3', $output);
        $this->assertStringNotContainsString('Excluded.php":{"errors"', $output);
        $this->assertStringNotContainsString('External.php":{"errors"', $output);
    }

    public function testEmptySelectionDisablesMigrationChecks(): void
    {
        $process = $this->analyse('foreign-keys-disabled.neon');

        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
    }

    public function testRejectsInvalidMigrationPathConfiguration(): void
    {
        $process = $this->analyse('foreign-keys-invalid.neon');
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertNotSame(0, $process->getExitCode(), $output);
        $this->assertStringContainsString('foreignKeys', $output);
        $this->assertStringContainsString('string', $output);
    }

    private function analyse(string $configuration): Process
    {
        $process = new Process([
            \PHP_BINARY,
            \dirname(__DIR__, 2) . '/vendor/bin/phpstan',
            'analyse',
            '--configuration=' . $configuration,
            '--error-format=json',
            '--no-progress',
            '--debug',
        ], \dirname(__DIR__) . '/Fixtures/consumer');
        $process->setTimeout(60);
        $process->run();

        return $process;
    }
}
