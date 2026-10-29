<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class SecurityCorrectnessConfigurationTest extends TestCase
{
    public function testBaseSetEnablesPortableChecksWithEmptyPersistenceScope(): void
    {
        $report = $this->report('php.neon');
        $this->assertSame(['assert.sideEffect', 'logging.fullRequestPayload', 'phpunit.providerSideEffect', 'security.disabledTlsVerification'], $report);
    }

    public function testPersistenceTestsRequireExplicitScope(): void
    {
        $this->assertSame(['assert.sideEffect', 'logging.fullRequestPayload', 'phpunit.databaseBuilderMock', 'phpunit.providerSideEffect', 'security.disabledTlsVerification'], $this->report('scoped.neon'));
    }

    public function testMalformedConfigurationFailsBeforeAnalysis(): void
    {
        $process = $this->analyse('invalid.neon');
        $this->assertNotSame(0, $process->getExitCode());
        $this->assertStringContainsString('forbiddenTypes', $process->getErrorOutput());
    }

    /**
     * @return list<string>
     */
    private function report(string $configuration): array
    {
        $process = $this->analyse($configuration);
        $this->assertSame(1, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());

        /** @var array{totals: array{errors: int}, files: array<string, array{messages: list<array{identifier: string}>}>} $report */
        $report = \json_decode(\mb_substr($process->getOutput(), \mb_strpos($process->getOutput(), '{"totals":') ?: 0), true, flags: \JSON_THROW_ON_ERROR);
        $this->assertSame(0, $report['totals']['errors']);
        $identifiers = [];

        foreach ($report['files'] as $file) {
            foreach ($file['messages'] as $message) {
                $identifiers[] = $message['identifier'];
            }
        }
        \sort($identifiers);

        return $identifiers;
    }

    private function analyse(string $configuration): Process
    {
        $process = new Process([\PHP_BINARY, \dirname(__DIR__, 2) . '/vendor/bin/phpstan', 'analyse', '-c', $configuration, '--error-format=json', '--no-progress', '--debug'], \dirname(__DIR__) . '/Fixtures/security-correctness');
        $process->setTimeout(60);
        $process->run();

        return $process;
    }
}
