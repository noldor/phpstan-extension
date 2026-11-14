<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class ParseUrlConfigurationTest extends TestCase
{
    public function testPhpSetReportsUsesOfTheGlobalParseUrlFunction(): void
    {
        $fixtureDirectory = __DIR__ . '/../Fixtures/ParseUrl';
        $process = new Process([
            \PHP_BINARY,
            \dirname(__DIR__, 2) . '/vendor/bin/phpstan',
            'analyse',
            '--configuration=php.neon',
            '--error-format=json',
            '--no-progress',
            '--debug',
        ], $fixtureDirectory);
        $process->setTimeout(60);
        $process->run();

        $output = $process->getOutput() . $process->getErrorOutput();
        $this->assertSame(1, $process->getExitCode(), $output);

        /** @var array{totals: array{errors: int}, files: array<string, array{messages: list<array{message: string, line: int, identifier: string}>}>} $report */
        $report = \json_decode(\mb_substr($process->getOutput(), \mb_strpos($process->getOutput(), '{"totals":') ?: 0), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame(0, $report['totals']['errors'], $output);
        $this->assertCount(1, $report['files'], $output);

        $messages = \array_values($report['files'])[0]['messages'];
        $this->assertSame([12, 13, 14, 15, 16], \array_column($messages, 'line'), $output);

        foreach ($messages as $message) {
            $this->assertSame('uri.parseUrl', $message['identifier']);
            $this->assertSame('Use Uri\\Rfc3986\\Uri instead of parse_url().', $message['message']);
        }
    }
}
