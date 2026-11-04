<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\Attributes\DataProvider;

final class HelperAllowancesConfigurationTest extends TestCase
{
    public function testAllowsOnlyTheConfiguredHelperInSelectedMethodsAndClassScopes(): void
    {
        $this->assertSame([19, 20, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 46, 47, 58, 65, 67, 74, 82], $this->lines('helper-allowances.neon'));
    }

    public function testWithoutAllowancesEveryServiceAcquisitionRemainsRestricted(): void
    {
        $this->assertSame([19, 20, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 46, 47, 53, 58, 65, 66, 67, 74, 81, 82], $this->lines('helper-allowances-base.neon'));
    }

    public function testEmptyClassOrMethodSelectionDoesNotGrantAGlobalAllowance(): void
    {
        $this->assertSame($this->lines('helper-allowances-base.neon'), $this->lines('helper-allowances-empty.neon'));
    }

    public function testRetainedResolverAndConstructorRestrictionsRemainActive(): void
    {
        $process = $this->analyse('helper-allowances-retained.neon');
        $output = $process->getOutput() . $process->getErrorOutput();
        $this->assertSame(1, $process->getExitCode(), $output);
        $this->assertStringContainsString('"file_errors":2', $output);
        $this->assertStringContainsString('lighthouse.retainedUnsafeDependency', $output);
        $this->assertStringContainsString('architecture.dependencyInjection', $output);
    }

    #[DataProvider('invalidConfigurations')]
    public function testRejectsInvalidConfigurationBeforeAnalysis(string $configuration, string $key): void
    {
        $process = $this->analyse('helper-allowances-' . $configuration . '.neon');
        $output = $process->getOutput() . $process->getErrorOutput();
        $this->assertNotSame(0, $process->getExitCode(), $output);
        $this->assertStringContainsString('allowedHelpers', $output);
        $this->assertStringContainsString($key, $output);
        $this->assertStringContainsString('Invalid configuration', $output);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function invalidConfigurations(): iterable
    {
        yield 'method must be a list' => ['invalid', 'methods'];

        yield 'methods are required' => ['missing-methods', 'methods'];

        yield 'function must be a string' => ['invalid-function', 'function'];

        yield 'classes must be a list' => ['invalid-classes', 'classes'];

        yield 'namespaces must contain strings' => ['invalid-namespaces', 'namespaces'];

        yield 'unknown keys are rejected' => ['unknown-key', 'typo'];
    }

    /**
     * @return list<int>
     */
    private function lines(string $configuration): array
    {
        $process = $this->analyse($configuration);
        $output = $process->getOutput() . $process->getErrorOutput();
        $offset = \mb_strpos($process->getOutput(), '{"totals":');
        $this->assertNotFalse($offset, $output);
        $report = \json_decode(\mb_substr($process->getOutput(), $offset), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertIsArray($report);
        $this->assertSame([], $report['errors']);
        $this->assertIsArray($report['files']);
        $lines = [];

        foreach ($report['files'] as $file) {
            $this->assertIsArray($file);
            $this->assertIsArray($file['messages']);

            foreach ($file['messages'] as $message) {
                $this->assertIsArray($message);
                $this->assertSame('architecture.dependencyInjection', $message['identifier']);
                $this->assertIsInt($message['line']);
                $lines[] = $message['line'];
            }
        }

        return $lines;
    }

    private function analyse(string $configuration): Process
    {
        $process = new Process([\PHP_BINARY, \dirname(__DIR__, 2) . '/vendor/bin/phpstan', 'analyse', '--configuration=' . $configuration, '--error-format=json', '--no-progress', '--debug'], \dirname(__DIR__) . '/Fixtures/dependency-access');
        $process->setTimeout(60);
        $process->run();

        return $process;
    }
}
