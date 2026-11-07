<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class GraphQLEnumDescriptionTest extends TestCase
{
    public function testLaravelSetReportsMissingDescriptionsOnRegisteredEnumDeclarations(): void
    {
        $root = \dirname(__DIR__, 2);
        $fixtures = $root . '/tests/Fixtures/graphql-enum-descriptions';
        $process = new Process([\PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse', '--error-format=json', '--no-progress', '--debug'], $fixtures);
        $process->run();

        $output = $process->getOutput();
        $this->assertSame(1, $process->getExitCode(), $output . $process->getErrorOutput());
        $reportStart = \mb_strpos($output, '{"totals":');
        $this->assertNotFalse($reportStart, $output);

        /** @var array{errors: list<string>, files: array<string, array{messages: list<array{line: int, identifier: string, message: string}>}>} $report */
        $report = \json_decode(\mb_substr($output, $reportStart), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame([], $report['errors']);
        $this->assertSame([$fixtures . '/Enums.php'], \array_keys($report['files']), $output);
        $messages = $report['files'][$fixtures . '/Enums.php']['messages'];

        $this->assertSame([21, 34, 42, 51], \array_column($messages, 'line'));
        $this->assertSame(
            ['lighthouse.enumDescription', 'lighthouse.enumCaseDescription', 'lighthouse.enumDescription', 'lighthouse.enumCaseDescription'],
            \array_column($messages, 'identifier'),
        );
        $this->assertStringContainsString('GraphQLEnumDescriptions\\UndocumentedEnum', $messages[0]['message']);
        $this->assertStringContainsString('GraphQLEnumDescriptions\\UndocumentedCase::INACTIVE', $messages[1]['message']);
    }
}
