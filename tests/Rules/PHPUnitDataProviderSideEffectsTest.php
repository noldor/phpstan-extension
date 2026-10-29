<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class PHPUnitDataProviderSideEffectsTest extends TestCase
{
    public function testFindsOnlyEagerEffectsInRegisteredProviders(): void
    {
        $this->assertDiagnostics('php.neon', ['Cases.php', 'ExternalProviders.php', 'InheritedCases.php']);
    }

    public function testExtendsProviderEffectsThroughConsumerSelectors(): void
    {
        $this->assertDiagnostics('custom.neon', ['CustomCases.php']);
    }

    public function testLaravelSetAddsDatabaseExecutionAndPersistingFactories(): void
    {
        $this->assertDiagnostics('laravel.neon', ['LaravelCases.php']);
    }

    /**
     * @param list<string> $fixtures
     */
    private function assertDiagnostics(string $configuration, array $fixtures): void
    {
        $directory = __DIR__ . '/../Fixtures/phpunit-providers';
        $process = new Process([
            \PHP_BINARY, \dirname(__DIR__, 2) . '/vendor/bin/phpstan', 'analyse',
            '--configuration=' . $configuration, '--error-format=json', '--no-progress', '--debug',
        ], $directory);
        $process->setTimeout(60);
        $process->run();
        $output = $process->getOutput();
        $start = \mb_strpos($output, '{"totals":');
        $this->assertNotFalse($start, $output . $process->getErrorOutput());

        /** @var array{errors: list<string>, files: array<string, array{messages: list<array{line: int, identifier: string}>}>} $report */
        $report = \json_decode(\mb_substr($output, $start), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertContains($process->getExitCode(), [0, 1], $output . $process->getErrorOutput());
        $this->assertSame([], $report['errors']);
        $actual = [];

        foreach ($report['files'] as $file => $details) {
            foreach ($details['messages'] as $message) {
                $actual[] = \basename($file) . ':' . $message['line'] . ':' . $message['identifier'];
            }
        }
        $expected = [];

        foreach ($fixtures as $fixture) {
            foreach (\file($directory . '/' . $fixture) ?: [] as $line => $source) {
                if (\str_contains($source, '@expect phpunit.providerSideEffect')) {
                    $expected[] = $fixture . ':' . ($line + 1) . ':phpunit.providerSideEffect';
                }
            }
        }
        \sort($actual);
        \sort($expected);
        $this->assertSame($expected, $actual, $output . $process->getErrorOutput());
    }
}
