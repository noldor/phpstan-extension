<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class LaravelValidationContractsConfigurationTest extends TestCase
{
    public function testLaravelSetConnectsConfiguredValidatorContracts(): void
    {
        $result = $this->analyse('full-set.neon');
        $output = $result->getOutput() . $result->getErrorOutput();
        $this->assertSame(1, $result->getExitCode(), $output);
        $this->assertStringContainsString('"identifier":"validation.methodOrder"', $output);
    }

    public function testFragmentRegistersConfiguredContracts(): void
    {
        $result = $this->analyse('enabled.neon');
        $this->assertSame(1, $result->getExitCode(), $result->getErrorOutput());
        $output = $result->getOutput();
        $start = \mb_strpos($output, '{"totals":');
        $this->assertNotFalse($start, $output . $result->getErrorOutput());

        /** @var array{errors: list<string>, files: array<string, array{messages: list<array{identifier: string}>}>} $report */
        $report = \json_decode(\mb_substr($output, $start), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame([], $report['errors']);
        $identifiers = [];

        foreach ($report['files'] as $file) {
            foreach ($file['messages'] as $message) {
                $identifiers[] = $message['identifier'];
            }
        }
        $counts = \array_count_values($identifiers);
        \ksort($counts);
        $this->assertSame([
            'validation.attributeOrder' => 1,
            'validation.fieldOrder' => 4,
            'validation.lighthouseMessageKey' => 1,
            'validation.messageCoverage' => 4,
            'validation.messageOrder' => 2,
            'validation.methodOrder' => 1,
            'validation.relativeInputPath' => 3,
        ], $counts);
    }

    public function testFragmentDefaultsToAnEmptyValidatorScope(): void
    {
        $result = $this->analyse('base.neon');
        $this->assertSame(0, $result->getExitCode(), $result->getOutput() . $result->getErrorOutput());
    }

    public function testInputFieldConfigurationIsValidatedBeforeAnalysis(): void
    {
        $result = $this->analyse('invalid.neon');
        $this->assertNotSame(0, $result->getExitCode());
        $this->assertStringContainsString('fields', $result->getErrorOutput());
        $this->assertStringContainsString('string', $result->getErrorOutput());
    }

    private function analyse(string $configuration): Process
    {
        $package = \dirname(__DIR__, 2);
        $process = new Process([\PHP_BINARY, $package . '/vendor/bin/phpstan', 'analyse', '--configuration=' . $configuration, '--error-format=json', '--no-progress', '--debug'], $package . '/tests/Fixtures/validation-contracts');
        $process->setTimeout(60);
        $process->run();

        return $process;
    }
}
