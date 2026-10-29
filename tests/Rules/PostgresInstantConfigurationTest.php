<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\Attributes\DataProvider;

final class PostgresInstantConfigurationTest extends TestCase
{
    #[DataProvider('activeConfigurations')]
    public function testLaravelSetChecksOnlyConfiguredOwnedPaths(string $configuration): void
    {
        $process = $this->analyse($configuration);
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertSame(1, $process->getExitCode(), $output);
        $this->assertStringContainsString('laravel.postgresqlInstant', $output);
        $this->assertStringContainsString('"file_errors":3', $output);
        $this->assertStringContainsString('/postgres-instants/Owned.php', $output);
        $this->assertStringNotContainsString('External.php":{"errors"', $output);
        $this->assertStringNotContainsString('OtherEngine.php":{"errors"', $output);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function activeConfigurations(): iterable
    {
        yield 'relative directory' => ['postgres-instants.neon'];

        yield 'absolute individual file' => ['postgres-instants-file.neon'];
    }

    #[DataProvider('inactiveConfigurations')]
    public function testAcceptsUnselectedOrExcludedDeclarations(string $configuration): void
    {
        $process = $this->analyse($configuration);

        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function inactiveConfigurations(): iterable
    {
        yield 'default opt in' => ['postgres-instants-default.neon'];

        yield 'disabled scope' => ['postgres-instants-disabled.neon'];

        yield 'PHP set' => ['postgres-instants-php.neon'];

        yield 'excluded individual file' => ['postgres-instants-file-excluded.neon'];
    }

    public function testLegacySetEnablesConfiguredPostgresChecks(): void
    {
        $process = $this->analyse('postgres-instants-legacy.neon');
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertSame(1, $process->getExitCode(), $output);
        $this->assertStringContainsString('laravel.postgresqlInstant', $output);
        $this->assertStringContainsString('"file_errors":3', $output);
    }

    public function testRejectsMalformedScopeConfiguration(): void
    {
        $process = $this->analyse('postgres-instants-invalid.neon');
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertNotSame(0, $process->getExitCode(), $output);
        $this->assertStringContainsString('postgresqlInstants', $output);
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
