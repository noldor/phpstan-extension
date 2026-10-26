<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class DtoInfrastructureUsageExtensionTest extends TestCase
{
    public function testRestrictsConfiguredInfrastructureInsideExplicitDataClasses(): void
    {
        $result = $this->analyse('phpstan.neon');

        $this->assertSame(1, $result['exitCode'], $result['output'] . $result['errors']);

        /** @var array{totals: array{errors: int, file_errors: int}, files: array<string, array{messages: list<array{message: string, line: int, identifier: string}>}>} $report */
        $report = \json_decode(\mb_substr($result['output'], \mb_strpos($result['output'], '{"totals":') ?: 0), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame(['errors' => 0, 'file_errors' => 14], $report['totals']);
        $this->assertCount(1, $report['files']);
        $messages = \array_values($report['files'])[0]['messages'];
        $this->assertSame([82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 107, 133], \array_column($messages, 'line'));

        foreach ($messages as $message) {
            $this->assertSame('architecture.dtoInfrastructureUsage', $message['identifier']);
            $this->assertStringContainsString('infrastructure', $message['message']);
        }
    }

    public function testAnEmptyDataClassScopeDisablesTheRestriction(): void
    {
        $result = $this->analyse('disabled.neon');

        $this->assertSame(0, $result['exitCode'], $result['output'] . $result['errors']);
        $this->assertStringContainsString('"file_errors":0', $result['output']);
    }

    /**
     * @return array{exitCode: int|null, output: string, errors: string}
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
            '--debug',
        ], $package . '/tests/Fixtures/dto-infrastructure');
        $process->setTimeout(60);
        $process->run();

        return [
            'exitCode' => $process->getExitCode(),
            'output' => $process->getOutput(),
            'errors' => $process->getErrorOutput(),
        ];
    }
}
