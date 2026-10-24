<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Noldors\PHPStan\Rules\LaravelValidationLocationRule;

/**
 * @extends RuleTestCase<LaravelValidationLocationRule>
 */
final class LaravelValidationLocationRuleTest extends RuleTestCase
{
    private const string MESSAGE = 'Define Laravel validation only in validator classes under the configured allowed paths; invoke a prepared validator here instead.';

    public function testRejectsInlineValidationOutsideValidators(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/app/UseCases/InlineValidation.php'], \array_map(
            static fn (int $line): array => [self::MESSAGE, $line],
            [...\range(27, 43), 48],
        ));
    }

    public function testRejectsRuleObjectsFacadesAndTypedUnions(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/app/UseCases/MoreInlineValidation.php'], \array_map(
            static fn (int $line): array => [self::MESSAGE, $line],
            [19, 20, 21, 24, 25, 27, 28, 31],
        ));
    }

    public function testRejectsChangingPreparedRuleBuildersButAllowsInspectionAndExecution(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/app/UseCases/RuleBuilders.php'], \array_map(
            static fn (int $line): array => [self::MESSAGE, $line],
            [15, 16, 17, 18],
        ));
    }

    #[DataProvider('validatorDirectories')]
    public function testAllowsDeclarationsInsideValidatorClasses(string $directory): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/app/' . $directory . '/Allowed.php'], []);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validatorDirectories(): iterable
    {
        yield 'GraphQL' => ['GraphQL/Validators'];

        yield 'HTTP' => ['Http/Requests'];

        yield 'shared validation' => ['Validation'];

        yield 'nested validator' => ['Validation/Administrators'];
    }

    public function testAllowsExecutingPreparedValidatorsAndUnrelatedApis(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/app/UseCases/PreparedValidation.php'], []);
    }

    public function testRejectsSimilarDirectoryNames(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/app/ValidationHelpers/Invalid.php'], [[self::MESSAGE, 13]]);
    }

    public function testReportsTheArchitectureErrorIdentifier(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/app/ValidationHelpers/Invalid.php']);

        $this->assertCount(1, $errors);
        $this->assertSame('architecture.validationLocation', $errors[0]->getIdentifier());
    }

    public function testRequiresAClassEvenInsideAllowedDirectories(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/app/Validation/GlobalValidation.php'], [[self::MESSAGE, 7]]);
    }

    public function testRejectsMisplacedFormRequestAndLighthouseRuleDeclarations(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/app/UseCases/MisplacedValidators.php'], [
            [self::MESSAGE, 12],
            [self::MESSAGE, 20],
        ]);
    }

    protected function getRule(): LaravelValidationLocationRule
    {
        return new LaravelValidationLocationRule(
            self::getFileHelper(),
            self::createReflectionProvider(),
            [
                __DIR__ . '/../Fixtures/app/GraphQL/Validators',
                __DIR__ . '/../Fixtures/app/Http/Requests',
                __DIR__ . '/../Fixtures/app/Validation',
            ],
        );
    }
}
