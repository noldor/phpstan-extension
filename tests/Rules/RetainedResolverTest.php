<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class RetainedResolverTest extends TestCase
{
    public function testTransitiveRetainedGraphsThroughTheLaravelSet(): void
    {
        $report = $this->analyse('enabled.neon');
        $actual = [];

        foreach ($report['files'] as $file => $details) {
            foreach ($details['messages'] as $message) {
                $actual[] = \basename($file) . ':' . $message['line'] . ':' . $message['identifier'];
            }
        }
        \sort($actual);
        $expected = [];

        foreach (\file(__DIR__ . '/../Fixtures/retained-resolvers/Roots.php') ?: [] as $index => $line) {
            if (1 === \preg_match('/@expect (\\S+)/', $line, $matches)) {
                $expected[] = 'Roots.php:' . ($index + 1) . ':' . $matches[1];
            }
        }
        \sort($expected);
        $this->assertSame($expected, $actual, \json_encode($report, \JSON_PRETTY_PRINT | \JSON_THROW_ON_ERROR));
        $messages = \json_encode($report, \JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('IndirectService', $messages);
        $this->assertStringContainsString('UnsafeService', $messages);
        $this->assertStringContainsString('Request', $messages);
    }

    public function testUnconfiguredResolversRemainUnaffected(): void
    {
        $this->assertSame([], $this->analyse('base.neon')['files']);
    }

    public function testConfigurationValidatesBoundaryLists(): void
    {
        $process = $this->process('invalid.neon');
        $this->assertNotSame(0, $process->getExitCode());
        $this->assertStringContainsString('safeClasses', $process->getErrorOutput());
    }

    public function testDynamicAbstractCannotSilentlyOverrideAReachedDependency(): void
    {
        $report = $this->analyse('dynamic.neon');
        $messages = $report['files'][\realpath(__DIR__ . '/../Fixtures/retained-resolvers/DynamicBindings.php')]['messages'] ?? [];
        $this->assertCount(4, $messages);

        foreach ($messages as $message) {
            $this->assertSame('lighthouse.retainedUnknownBinding', $message['identifier']);
        }
    }

    public function testUnreflectableDeclaredObjectsRemainUnknownDependencies(): void
    {
        $report = $this->analyse('unknown.neon');
        $messages = $report['files'][\realpath(__DIR__ . '/../Fixtures/retained-resolvers/UnknownClass.php')]['messages'] ?? [];
        $this->assertContains('lighthouse.retainedUnknownDependency', \array_column($messages, 'identifier'));
    }

    public function testCachedAnalysisRechecksChangedTransitiveTypesAndBindings(): void
    {
        $root = \dirname(__DIR__, 2);
        $directory = $root . '/build/retained-cache-' . \bin2hex(\random_bytes(6));
        $files = new Filesystem();
        $files->copyDirectory(__DIR__ . '/../Fixtures/retained-cache', $directory);
        $configuration = 'includes: [' . $root . "/src/laravel.neon]\nparameters:\n    level: 0\n    paths: [.]\n    excludePaths: [cache]\n    tmpDir: " . $directory . "/cache\n    noldors:\n        laravel:\n            retainedResolvers:\n                classes: [RetainedCache\\Root]\n                bindingSourcePaths: [Bindings.php]\n";
        $files->put($directory . '/phpstan.neon', $configuration);

        try {
            $this->assertSame([], $this->cachedReport($directory)['files']);
            $this->assertFileExists($directory . '/cache/resultCache.php');
            $services = \file_get_contents($directory . '/Services.php');
            $this->assertNotFalse($services);
            $files->put($directory . '/Services.php', \str_replace('class Safe implements Contract {}', 'class Safe implements Contract { public function __construct(private readonly \\Illuminate\\Http\\Request $request) {} }', $services));
            $this->assertCachedUnsafeChain($this->cachedReport($directory));
            $files->put($directory . '/Services.php', $services);
            $this->assertSame([], $this->cachedReport($directory)['files']);
            $bindings = \file_get_contents($directory . '/Bindings.php');
            $this->assertNotFalse($bindings);
            $files->put($directory . '/Bindings.php', \str_replace('Safe::class', 'Unsafe::class', $bindings));
            $this->assertCachedUnsafeChain($this->cachedReport($directory));
        } finally {
            $files->deleteDirectory($directory);
        }
    }

    /**
     * @param array{errors: list<string>, files: array<string, array{messages: list<array{line: int, identifier: string, message: string}>}>} $report
     */
    private function assertCachedUnsafeChain(array $report): void
    {
        $this->assertCount(1, $report['files']);
        $messages = \array_values($report['files'])[0]['messages'];
        $this->assertCount(1, $messages);
        $this->assertSame('lighthouse.retainedUnsafeDependency', $messages[0]['identifier']);
        $this->assertStringContainsString('Request', $messages[0]['message']);
    }

    /**
     * @return array{errors: list<string>, files: array<string, array{messages: list<array{line: int, identifier: string, message: string}>}>}
     */
    private function cachedReport(string $directory): array
    {
        $root = \dirname(__DIR__, 2);
        $process = new Process([\PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse', '--no-progress', '--error-format=json', '-vv'], $directory);
        $process->setTimeout(90);
        $process->run();
        $this->assertStringContainsString('Result cache is saved.', $process->getErrorOutput() . $process->getOutput());

        return $this->report($process);
    }

    /**
     * @return array{errors: list<string>, files: array<string, array{messages: list<array{line: int, identifier: string, message: string}>}>}
     */
    private function analyse(string $configuration): array
    {
        return $this->report($this->process($configuration));
    }

    /**
     * @return array{errors: list<string>, files: array<string, array{messages: list<array{line: int, identifier: string, message: string}>}>}
     */
    private function report(Process $process): array
    {
        $output = $process->getOutput();
        $start = \mb_strpos($output, '{"totals":');
        $this->assertNotFalse($start, $output . $process->getErrorOutput());

        /** @var array{errors: list<string>, files: array<string, array{messages: list<array{line: int, identifier: string, message: string}>}>} $report */
        $report = \json_decode(\mb_substr($output, $start), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame([], $report['errors']);

        return $report;
    }

    private function process(string $configuration): Process
    {
        $root = \dirname(__DIR__, 2);
        $process = new Process([\PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse', '--configuration=' . $configuration, '--error-format=json', '--no-progress', '--debug'], $root . '/tests/Fixtures/retained-resolvers');
        $process->setTimeout(90);
        $process->run();

        return $process;
    }
}
