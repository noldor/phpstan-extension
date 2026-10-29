<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class DependencyAccessConfigurationTest extends TestCase
{
    public function testRestrictsFacadesHelpersAndServiceLocatorsOnlyInsideSelectedDiClasses(): void
    {
        $report = $this->analyse('phpstan.neon');
        $messages = $this->messages($report, 'architecture.dependencyInjection');
        $this->assertSame([34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48], \array_column($messages, 'line'));
    }

    public function testKeepsTransportTypesAndGlobalActorsOutsideUseCases(): void
    {
        $report = $this->analyse('phpstan.neon');
        $this->assertSame([61, 61, 64, 64], \array_column($this->messages($report, 'architecture.useCaseTransport'), 'line'));
        $this->assertSame([66, 67, 68, 69, 70, 71, 72], \array_column($this->messages($report, 'architecture.useCaseActor'), 'line'));
    }

    public function testAllHelperModeAlsoRestrictsPureLaravelHelpers(): void
    {
        $report = $this->analyse('all-helpers.neon');
        $messages = $this->messages($report, 'architecture.dependencyInjection');
        $this->assertSame([34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 50], \array_column($messages, 'line'));
    }

    public function testFacadeSourceIdentitySurvivesLarastanMethodForwarding(): void
    {
        $report = $this->analyse('larastan.neon');
        $this->assertSame([34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48], \array_column($this->messages($report, 'architecture.dependencyInjection'), 'line'));
    }

    public function testTransportRestrictionsIncludePhpDocTypes(): void
    {
        $report = $this->analyse('phpdoc.neon');
        $this->assertSame([12, 15], \array_column($this->messages($report, 'architecture.useCaseTransport'), 'line'));
    }

    public function testSupportsConfiguredTransportAndLocatorTypesAndExplicitExclusions(): void
    {
        $report = $this->analyse('boundaries.neon');
        $this->assertSame([25], \array_column($this->messages($report, 'architecture.useCaseTransport'), 'line'));
        $this->assertSame([28, 30, 51, 53], \array_column($this->messages($report, 'architecture.useCaseActor'), 'line'));
        $this->assertSame([28, 29, 30, 31, 32, 33, 35, 50, 51, 53], \array_column($this->messages($report, 'architecture.dependencyInjection'), 'line'));
    }

    public function testEmptyScopesDisableAllRestrictions(): void
    {
        $report = $this->analyse('disabled.neon');
        $this->assertSame([], $this->messages($report, 'architecture.dependencyInjection'));
        $this->assertSame([], $this->messages($report, 'architecture.useCaseTransport'));
        $this->assertSame([], $this->messages($report, 'architecture.useCaseActor'));
    }

    /**
     * @return list<array{identifier: string, line: int, message: string}>
     */
    private function analyse(string $configuration): array
    {
        $process = new Process([\PHP_BINARY, \dirname(__DIR__, 2) . '/vendor/bin/phpstan', 'analyse', '--configuration=' . $configuration, '--error-format=json', '--no-progress', '--debug'], \dirname(__DIR__) . '/Fixtures/dependency-access');
        $process->setTimeout(60);
        $process->run();
        $output = $process->getOutput() . $process->getErrorOutput();
        $offset = \mb_strpos($process->getOutput(), '{"totals":');
        $this->assertNotFalse($offset, $output);

        $report = \json_decode(\mb_substr($process->getOutput(), $offset), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertIsArray($report);
        $this->assertSame([], $report['errors']);
        $this->assertIsArray($report['files']);
        $messages = [];

        foreach ($report['files'] as $file) {
            $this->assertIsArray($file);
            $this->assertIsArray($file['messages']);

            foreach ($file['messages'] as $message) {
                $this->assertIsArray($message);
                $this->assertIsString($message['identifier']);
                $this->assertIsString($message['message']);
                $this->assertIsInt($message['line']);
                $this->assertContains($message['identifier'], ['architecture.dependencyInjection', 'architecture.useCaseTransport', 'architecture.useCaseActor']);
                $messages[] = ['identifier' => $message['identifier'], 'message' => $message['message'], 'line' => $message['line']];
            }
        }

        return $messages;
    }

    /**
     * @param list<array{identifier: string, line: int, message: string}> $report
     *
     * @return list<array{identifier: string, line: int, message: string}>
     */
    private function messages(array $report, string $identifier): array
    {
        return \array_values(\array_filter($report, static fn (array $message): bool => $message['identifier'] === $identifier));
    }
}
