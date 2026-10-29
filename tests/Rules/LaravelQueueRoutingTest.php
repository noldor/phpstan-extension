<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class LaravelQueueRoutingTest extends TestCase
{
    public function testRoutingContractsAndNativeAttributePreferences(): void
    {
        $this->assertSame($this->expectedDiagnostics(), $this->analyse('enabled.neon'));
    }

    public function testEmptyRoleScopesDoNotEnableQueueContracts(): void
    {
        $this->assertSame([], $this->analyse('base.neon'));
    }

    /**
     * @return list<string>
     */
    private function expectedDiagnostics(): array
    {
        $expected = [];

        foreach (\glob(__DIR__ . '/../Fixtures/queue-routing/*.php') ?: [] as $file) {
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
     * @return list<string>
     */
    private function analyse(string $configuration): array
    {
        $package = \dirname(__DIR__, 2);
        $process = new Process([\PHP_BINARY, $package . '/vendor/bin/phpstan', 'analyse', '--configuration=' . $configuration, '--error-format=json', '--no-progress', '--debug'], $package . '/tests/Fixtures/queue-routing');
        $process->setTimeout(90);
        $process->run();
        $output = $process->getOutput();
        $jsonStart = \mb_strpos($output, '{"totals":');
        $this->assertNotFalse($jsonStart, $output . $process->getErrorOutput());

        /** @var array{errors: list<string>, files: array<string, array{messages: list<array{line: int, identifier: string}>}>} $report */
        $report = \json_decode(\mb_substr($output, $jsonStart), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame([], $report['errors']);
        $actual = [];

        foreach ($report['files'] as $file => $details) {
            foreach ($details['messages'] as $message) {
                $actual[] = \basename($file) . ':' . $message['line'] . ':' . $message['identifier'];
            }
        }
        \sort($actual);

        return $actual;
    }
}
