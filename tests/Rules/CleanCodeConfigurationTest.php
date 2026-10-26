<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class CleanCodeConfigurationTest extends TestCase
{
    public function testPhpSetEnablesReferenceAndPreviousExceptionChecks(): void
    {
        $process = $this->analyse('clean-code.neon');
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertSame(1, $process->getExitCode(), $output);
        $this->assertStringContainsString('api.referenceParameter', $output);
        $this->assertStringContainsString('exceptions.missingPrevious', $output);
        $this->assertStringNotContainsString('encapsulation.mutableCollectionExposure', $output);
        $this->assertStringNotContainsString('architecture.dtoInfrastructureUsage', $output);
    }

    public function testOwnerAndDtoScopesEnableTheirConfiguredChecks(): void
    {
        $process = $this->analyse('clean-code-policies.neon');
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertSame(1, $process->getExitCode(), $output);
        $this->assertStringContainsString('encapsulation.mutableCollectionExposure', $output);
        $this->assertStringContainsString('architecture.dtoInfrastructureUsage', $output);
        $this->assertStringContainsString('file_get_contents', $output);
        $this->assertStringNotContainsString('Filesystem::get()', $output);
    }

    public function testPhpSetChecksJsonCallsInsideTestDirectories(): void
    {
        $process = $this->analyse('clean-code.neon');
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertSame(1, $process->getExitCode(), $output);
        $this->assertStringContainsString('json.missingThrowOnError', $output);
        $this->assertStringContainsString('/tests/Json.php', $output);
    }

    public function testLaravelSetAddsItsInfrastructureSelectors(): void
    {
        $process = $this->analyse('clean-code-laravel.neon');
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertSame(1, $process->getExitCode(), $output);
        $this->assertStringContainsString('architecture.dtoInfrastructureUsage', $output);
        $this->assertStringContainsString('Filesystem::get()', $output);
    }

    public function testDoesNotAttributeReboundExceptionToTheOriginalCatch(): void
    {
        $process = $this->analyse('clean-code-rebinding.neon');
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertSame(1, $process->getExitCode(), $output);
        $this->assertStringContainsString('api.referenceParameter', $output);
        $this->assertStringNotContainsString('exceptions.missingPrevious', $output);
    }

    public function testRejectsMalformedCleanCodeConfiguration(): void
    {
        $process = $this->analyse('invalid-clean-code.neon');
        $output = $process->getOutput() . $process->getErrorOutput();

        $this->assertNotSame(0, $process->getExitCode(), $output);
        $this->assertStringContainsString('mutableCollections', $output);
        $this->assertStringContainsString('owners', $output);
    }

    private function analyse(string $configuration): Process
    {
        $process = new Process([
            \PHP_BINARY,
            \dirname(__DIR__, 2) . '/vendor/bin/phpstan',
            'analyse',
            '--configuration=' . $configuration,
            '--error-format=json',
            '--no-progress',
            '--debug',
        ], \dirname(__DIR__) . '/Fixtures/consumer');
        $process->setTimeout(60);
        $process->run();

        return $process;
    }
}
