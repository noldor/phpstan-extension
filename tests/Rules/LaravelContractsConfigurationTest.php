<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class LaravelContractsConfigurationTest extends TestCase
{
    public function testConfiguredContractsReportOnlyTheDocumentedViolations(): void
    {
        $report = $this->analyse('enabled.neon');
        $actual = [];

        foreach ($report['files'] as $file => $details) {
            foreach ($details['messages'] as $message) {
                $actual[] = \basename($file) . ':' . $message['line'] . ':' . $message['identifier'];
            }
        }
        \sort($actual);
        $this->assertSame($this->expectedDiagnostics(), $actual);
    }

    public function testEmptyScopesPreserveExistingLaravelBehaviour(): void
    {
        $report = $this->analyse('base.neon');
        $this->assertSame(['errors' => 0, 'file_errors' => 0], $report['totals']);
    }

    public function testPhpSetDoesNotEnableApplicationContracts(): void
    {
        $report = $this->analyse('php-only.neon');
        $this->assertSame(['errors' => 0, 'file_errors' => 0], $report['totals']);
    }

    public function testLegacySetPreservesTheSameConfiguredContracts(): void
    {
        $this->assertSame($this->analyse('enabled.neon'), $this->analyse('legacy.neon'));
    }

    public function testInvalidRoleConfigurationFailsBeforeAnalysis(): void
    {
        $result = $this->runAnalysis('invalid.neon');
        $this->assertNotSame(0, $result['exitCode']);
        $this->assertStringContainsString('namespaces', $result['errors']);
        $this->assertStringContainsString('string', $result['errors']);
    }

    /**
     * @return list<string>
     */
    private function expectedDiagnostics(): array
    {
        $expected = [];

        foreach (\glob(__DIR__ . '/../Fixtures/laravel-contracts/*.php') ?: [] as $file) {
            foreach (\file($file) ?: [] as $index => $line) {
                if (\preg_match('/@expect ([A-Za-z. ]+)/', $line, $matches) !== 1) {
                    continue;
                }

                foreach (\explode(' ', \mb_trim($matches[1])) as $identifier) {
                    $expected[] = \basename($file) . ':' . ($index + 1) . ':' . $identifier;
                }
            }
        }
        \sort($expected);

        return $expected;
    }

    /**
     * @return array{totals: array{errors: int, file_errors: int}, errors: list<string>, files: array<string, array{messages: list<array{line: int, identifier: string}>}>}
     */
    private function analyse(string $configuration): array
    {
        $result = $this->runAnalysis($configuration);
        $this->assertContains($result['exitCode'], [0, 1], $result['output'] . $result['errors']);
        $this->assertStringStartsWith('{', $result['output'], $result['errors']);

        /** @var array{totals: array{errors: int, file_errors: int}, errors: list<string>, files: array<string, array{messages: list<array{line: int, identifier: string}>}>} $report */
        $report = \json_decode($result['output'], true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame([], $report['errors'], $result['errors']);

        return $report;
    }

    /**
     * @return array{exitCode: int|null, output: string, errors: string}
     */
    private function runAnalysis(string $configuration): array
    {
        $package = \dirname(__DIR__, 2);
        $process = new Process([\PHP_BINARY, $package . '/vendor/bin/phpstan', 'analyse', '--configuration=' . $configuration, '--error-format=json', '--no-progress', '--debug'], $package . '/tests/Fixtures/laravel-contracts');
        $process->setTimeout(60);
        $process->run();
        $output = $process->getOutput();
        $jsonStart = \mb_strpos($output, '{"totals":');

        return ['exitCode' => $process->getExitCode(), 'output' => false === $jsonStart ? $output : \mb_substr($output, $jsonStart), 'errors' => $process->getErrorOutput()];
    }
}
