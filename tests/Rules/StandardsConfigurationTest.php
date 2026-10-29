<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class StandardsConfigurationTest extends TestCase
{
    public function testLaravelSetConnectsConfiguredEnumLighthouseAndDependencyContracts(): void
    {
        $process = $this->analyse('enabled.neon');
        $output = $process->getOutput() . $process->getErrorOutput();
        $this->assertSame(1, $process->getExitCode(), $output);

        foreach (['laravel.enumName', 'laravel.enumCase', 'laravel.enumLocation', 'lighthouse.validatorBase', 'lighthouse.validatorFinal', 'lighthouse.resolverInvokable', 'lighthouse.subscriptionFinal', 'api.dependencyPromotion', 'api.dependencyReadonly'] as $identifier) {
            $this->assertStringContainsString('"identifier":"' . $identifier . '"', $output);
        }
    }

    public function testUnconfiguredScopesDoNotGuessApplicationRoles(): void
    {
        $process = $this->analyse('base.neon');
        $this->assertSame(0, $process->getExitCode(), $process->getOutput() . $process->getErrorOutput());
    }

    public function testConfigurationValidatesRoleLists(): void
    {
        $process = $this->analyse('invalid.neon');
        $this->assertNotSame(0, $process->getExitCode());
        $this->assertStringContainsString('namespaces', $process->getErrorOutput());
    }

    private function analyse(string $configuration): Process
    {
        $root = \dirname(__DIR__, 2);
        $process = new Process([\PHP_BINARY, $root . '/vendor/bin/phpstan', 'analyse', '--configuration=' . $configuration, '--error-format=json', '--no-progress', '--debug'], $root . '/tests/Fixtures/standards');
        $process->setTimeout(60);
        $process->run();

        return $process;
    }
}
