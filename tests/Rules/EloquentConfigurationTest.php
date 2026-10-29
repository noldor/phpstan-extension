<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\Attributes\DataProvider;

final class EloquentConfigurationTest extends TestCase
{
    /**
     * @param list<string> $identifiers
     */
    #[DataProvider('invalidCases')]
    public function testReportsModelContractsThroughConsumerConfiguration(string $configuration, array $identifiers): void
    {
        $result = $this->analyse($configuration);
        $this->assertSame(1, $result['code'], $result['output']);
        $this->assertSame($identifiers, $result['identifiers'], $result['output']);
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function invalidCases(): iterable
    {
        yield 'configuration and dates' => ['core-invalid.neon', [
            'laravel.modelCasts',
            'laravel.modelConfiguration',
            'laravel.modelDateFormat',
            'laravel.modelJsonDateFormat',
            'laravel.modelJsonDateFormat',
        ]];

        yield 'registered observers and morph maps' => ['registrations-invalid.neon', [
            'laravel.morphAlias',
            'laravel.morphAlias',
            'laravel.morphAlias',
            'laravel.morphMap',
            'laravel.morphMapUnverifiable',
            'laravel.observerFinal',
            'laravel.observerFinal',
            'laravel.observerFinal',
        ]];

        yield 'schema and property contracts' => ['properties-invalid.neon', [
            'laravel.computedAttribute',
            'laravel.computedAttribute',
            'laravel.modelPropertyName',
            'laravel.modelPropertyOrder',
            'laravel.modelPropertyReadOnly',
            'laravel.modelPropertyReadOnly',
            'laravel.modelPropertyReadOnly',
            'laravel.modelPropertyType',
            'laravel.modelPropertyType',
            'laravel.modelPropertyType',
        ]];

        yield 'effective pivot configuration' => ['pivots-invalid.neon', [
            'laravel.pivotChaperone',
            'laravel.pivotChaperone',
            'laravel.pivotChaperone',
            'laravel.pivotClass',
            'laravel.pivotInverse',
            'laravel.pivotInverse',
        ]];

        yield 'database nullability independent of annotations' => ['nullability-invalid.neon', ['laravel.modelPropertyType']];

        yield 'storage overrides and inherited casts' => ['date-overrides-invalid.neon', ['laravel.modelDateFormat', 'laravel.modelDateFormat', 'laravel.modelJsonDateFormat']];

        yield 'review regression cases' => ['review-invalid.neon', [
            'laravel.computedAttribute', 'laravel.modelDateFormat',
            'laravel.modelPropertyReadOnly', 'laravel.modelPropertyReadOnly', 'laravel.modelPropertyReadOnly',
            'laravel.morphAlias', 'laravel.pivotChaperone',
        ]];

        yield 'native list-derived alias' => ['list-alias-invalid.neon', ['laravel.morphAlias']];

        yield 'second review regressions' => ['second-review-invalid.neon', ['laravel.computedAttribute', 'laravel.morphMap']];

        yield 'distinct methods on the same line' => ['serializer-identity-invalid.neon', ['laravel.modelJsonDateFormat']];

        yield 'setter read types and actual never getter' => ['setter-only-invalid.neon', ['laravel.modelPropertyType', 'laravel.modelPropertyType']];

        yield 'constructor after another method on the same line' => ['constructor-source-invalid.neon', ['laravel.modelDateFormat', 'laravel.modelDateFormat']];

        yield 'accessor supplied by a trait in the same file' => ['accessor-flow-invalid.neon', ['laravel.modelPropertyType']];
    }

    #[DataProvider('validCases')]
    public function testAcceptsSupportedConfigurationsAndBoundaries(string $configuration): void
    {
        $result = $this->analyse($configuration);
        $this->assertSame(0, $result['code'], $result['output']);
        $this->assertSame([], $result['identifiers']);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validCases(): iterable
    {
        yield 'core, inherited serializer and dynamic exceptions' => ['core-valid.neon'];

        yield 'registrations in all supported forms and literal boundaries' => ['registrations-valid.neon'];

        yield 'schema, accessors, query properties, traits and writable key' => ['properties-valid.neon'];

        yield 'pivot variables, morph directions and custom accessors' => ['pivots-valid.neon'];

        yield 'disabled scope' => ['disabled.neon'];

        yield 'PHP set without Laravel bridge' => ['php.neon'];

        yield 'review legitimate cases' => ['review-valid.neon'];

        yield 'public writable and readonly accessors at max level' => ['public-accessors-valid.neon'];

        yield 'analyzed trait serializer without trust declarations' => ['core-untrusted.neon'];

        yield 'setter-only raw, cast and nullable readable values' => ['setter-only-valid.neon'];

        yield 'assigned, indirect and mixed legacy-modern accessors' => ['accessor-flow-valid.neon'];

        yield 'constructors from different classes on one line' => ['constructor-source-valid.neon'];
    }

    public function testValidatesConsumerConfiguration(): void
    {
        $result = $this->analyse('invalid-config.neon');
        $this->assertNotSame(0, $result['code']);
        $this->assertStringContainsString('eloquent', $result['output']);
        $this->assertStringContainsString('string', $result['output']);
    }

    /**
     * @return array{code: int|null, output: string, identifiers: list<string>}
     */
    private function analyse(string $configuration): array
    {
        $process = new Process([
            \PHP_BINARY, \dirname(__DIR__, 2) . '/vendor/bin/phpstan', 'analyse',
            '--configuration=' . $configuration, '--error-format=json', '--no-progress', '--debug',
        ], __DIR__ . '/../Fixtures/eloquent');
        $process->setTimeout(60);
        $process->run();
        $output = $process->getOutput() . $process->getErrorOutput();
        $start = \mb_strpos($process->getOutput(), '{"totals"');
        $identifiers = [];

        if (false !== $start) {
            $data = \json_decode(\mb_substr($process->getOutput(), $start), true, flags: \JSON_THROW_ON_ERROR);
            $this->assertIsArray($data);
            $this->assertIsArray($data['files']);

            foreach ($data['files'] as $file) {
                $this->assertIsArray($file);
                $this->assertIsArray($file['messages']);

                foreach ($file['messages'] as $message) {
                    $this->assertIsArray($message);
                    $this->assertIsString($message['identifier']);
                    $identifiers[] = $message['identifier'];
                }
            }
            $this->assertSame([], $data['errors'], $output);
        }
        \sort($identifiers);

        return ['code' => $process->getExitCode(), 'output' => $output, 'identifiers' => $identifiers];
    }
}
