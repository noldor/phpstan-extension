<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Validation\Paths;
use Noldors\PHPStan\Validation\RuleNames;
use Noldors\PHPStan\Eloquent\MethodSource;
use Noldors\PHPStan\Validation\FieldOrder;
use Noldors\PHPStan\Validation\MethodArrays;
use Noldors\PHPStan\Configuration\ClassScope;
use Noldors\PHPStan\Validation\Configuration;
use Noldors\PHPStan\Validation\RuleListItems;
use Noldors\PHPStan\Validation\ConstantSource;
use PHPUnit\Framework\Attributes\DataProvider;
use Noldors\PHPStan\Validation\MessageCoverage;
use Noldors\PHPStan\Validation\RuleExpressions;
use Noldors\PHPStan\Validation\FileBuilderRules;
use Noldors\PHPStan\Validation\FluentBuilderRules;
use Noldors\PHPStan\Validation\NestedBuilderRules;
use Noldors\PHPStan\Validation\BuiltinBuilderRules;
use Noldors\PHPStan\Validation\SizeMessageBranches;
use Noldors\PHPStan\Rules\LaravelValidationContractRule;

/**
 * @extends RuleTestCase<LaravelValidationContractRule>
 */
final class LaravelValidationContractRuleTest extends RuleTestCase
{
    public function testCoverageNamesOnlyTheUncoveredBuiltinFieldRulePairs(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/validation-contracts/Messages.php']);
        $this->assertSame([
            'Provide an explicit public validation message for title.max.',
            'Provide an explicit public validation message for ownerId.exists.',
        ], \array_map(static fn ($error): string => $error->getMessage(), $errors));
    }

    public function testSizeMessagesCoverOnlyTheirReachableRuntimeBranches(): void
    {
        $this->assertMissingPairs('SizeBranches.php', ['name.max', 'required.required', 'nullValue.required', 'name.max']);
    }

    public function testBuiltinBuilderProvenanceRetainsEveryExplicitFailure(): void
    {
        $this->assertMissingPairs('BuiltinObjects.php', [
            'name.min', 'picture.dimensions', 'count.numeric',
            'mail.email', 'mail.max', 'date.date_format', 'date.before', 'date.after_or_equal', 'date.before_or_equal',
            'ip.ipv4', 'text.string', 'text.alpha_num', 'text.min', 'text.max',
            'number.numeric', 'number.integer', 'number.gt', 'number.min_digits',
            'file.file', 'file.mimetypes', 'file.mimes', 'file.extensions', 'file.between', 'file.encoding',
            'image.file', 'image.image', 'image.dimensions',
            'password.string', 'password.min', 'password.max', 'password.password.mixed', 'password.password.letters',
            'password.password.numbers', 'password.password.symbols', 'password.password.uncompromised',
            'direct.dimensions', 'direct.numeric', 'required.required', 'requiredUnless.required',
            'prohibited.prohibited', 'prohibitedUnless.prohibited',
        ]);
    }

    public function testBuiltinFactoryCatalogIsNotTreatedAsCustomMessageOwnership(): void
    {
        $this->assertMissingPairs('BuiltinCatalog.php', [
            'accepted.accepted', 'activeUrl.active_url', 'base64.base64', 'boolean.boolean', 'declined.declined',
            'filled.filled', 'hexColor.hex_color', 'json.json', 'list.list', 'macAddress.mac_address',
            'missing.missing', 'present.present', 'prohibited.prohibited', 'required.required', 'ulid.ulid',
            'acceptedIf.accepted_if', 'arrayKeys.array_keys', 'can.Illuminate\\Validation\\Rules\\Can', 'confirmed.confirmed', 'contains.contains',
            'currentPassword.current_password', 'declinedIf.declined_if', 'distinct.distinct', 'doesntContain.doesnt_contain',
            'enum.Illuminate\\Validation\\Rules\\Enum', 'exists.exists', 'in.in', 'inArray.in_array', 'inArrayKeys.in_array_keys',
            'missingIf.missing_if', 'missingUnless.missing_unless', 'missingWith.missing_with', 'missingWithAll.missing_with_all',
            'notIn.not_in', 'notRegex.not_regex', 'presentIf.present_if', 'presentUnless.present_unless',
            'presentWith.present_with', 'presentWithAll.present_with_all', 'prohibitedIfAccepted.prohibited_if_accepted',
            'prohibitedIfDeclined.prohibited_if_declined', 'prohibits.prohibits', 'regex.regex', 'requiredArrayKeys.required_array_keys',
            'requiredIfAccepted.required_if_accepted', 'requiredIfDeclined.required_if_declined', 'requiredWith.required_with',
            'requiredWithAll.required_with_all', 'requiredWithout.required_without', 'requiredWithoutAll.required_without_all',
            'timezone.timezone', 'unique.unique', 'url.url', 'uuid.uuid', 'anyOf.Illuminate\\Validation\\Rules\\AnyOf',
        ]);
    }

    public function testBuiltinBoundariesPreserveNativeCompositionAndNestedContexts(): void
    {
        $this->assertMissingPairs('BuiltinBoundaries.php', ['upload.max', 'mergedBuiltin.min', 'conditional.min']);
    }

    public function testNativeEdgeCasesRetainImplicitRulesAndRejectEmptyMessages(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/validation-contracts/NativeEdgeCases.php']);
        $this->assertSame([
            'Provide an explicit public validation message for digits.integer.',
            'Provide an explicit public validation message for range.integer.',
            'Provide an explicit public validation message for exact.integer.',
            'Provide an explicit public validation message for name.required.',
            ...\array_map(static fn (string $rule): string => 'Use a field-specific Lighthouse message key instead of the generic rule key password.' . $rule . '.', ['mixed', 'letters', 'numbers', 'symbols', 'uncompromised']),
        ], \array_map(static fn ($error): string => $error->getMessage(), $errors));
    }

    public function testComparisonsCanSelectNumericMessagesWithoutChangingOtherSizeConstraints(): void
    {
        $this->assertMissingPairs('ComparisonSizeBranches.php', ['gt.gt', 'gte.gte', 'lt.lt', 'lte.lte']);
    }

    public function testNativeObjectOverridesFollowTheActualLaravelClassKeyContract(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/validation-contracts/NativeObjectMessages.php']);
        $this->assertSame([
            'Provide an explicit public validation message for unsafe.Illuminate\\Validation\\Rules\\Enum.',
            'Use a field-specific Lighthouse message key instead of the generic rule key Illuminate\\Validation\\Rules\\Enum.',
            'Use a field-specific Lighthouse message key instead of the generic rule key Illuminate\\Validation\\Rules\\File.',
        ], \array_map(static fn ($error): string => $error->getMessage(), $errors));
    }

    public function testBailedNativeFileGuardsNarrowOnlyLaterSizeAndComparisonFailures(): void
    {
        $this->assertMissingPairs('BailedNativeFiles.php', ['unguarded.max', 'unguarded.gt', 'before.max']);
    }

    /**
     * @param list<string> $identifiers
     */
    #[DataProvider('contracts')]
    public function testValidationContracts(string $file, array $identifiers): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/validation-contracts/' . $file]);
        $this->assertSame($identifiers, \array_map(static fn ($error): string|null => $error->getIdentifier(), $errors));
    }

    /**
     * @return iterable<string, array{string, list<string>}>
     */
    public static function contracts(): iterable
    {
        yield 'method order' => ['Methods.php', ['validation.methodOrder']];

        yield 'relationship and parent order' => ['Fields.php', ['validation.fieldOrder', 'validation.fieldOrder', 'validation.fieldOrder', 'validation.fieldOrder']];

        yield 'message and attribute order' => ['Groups.php', ['validation.messageOrder', 'validation.attributeOrder', 'validation.messageOrder']];

        yield 'builtin coverage and custom rule ownership' => ['Messages.php', ['validation.messageCoverage', 'validation.messageCoverage']];

        yield 'Lighthouse keys and explicit input bindings' => ['Lighthouse.php', [
            'validation.lighthouseMessageKey',
            'validation.relativeInputPath', 'validation.relativeInputPath', 'validation.relativeInputPath',
        ]];

        yield 'inherited rules require effective messages' => ['Inherited.php', ['validation.messageCoverage', 'validation.messageCoverage']];

        yield 'static composition wildcard messages translations and custom objects' => ['Valid.php', []];

        yield 'unresolved composition remains unknown' => ['Unknown.php', []];

        yield 'unselected validator remains outside the scope' => ['Excluded.php', []];
    }

    protected function getRule(): LaravelValidationContractRule
    {
        $arrays = new MethodArrays(new MethodSource(self::getParser()), new ConstantSource(self::getParser()), self::createReflectionProvider());
        $paths = new Paths($arrays);
        $names = new RuleNames(self::createReflectionProvider());
        $expressions = new RuleExpressions($arrays, $names, new BuiltinBuilderRules($names, new FluentBuilderRules(), new FileBuilderRules(), new NestedBuilderRules()), new RuleListItems());

        return new LaravelValidationContractRule(
            new ClassScope(namespaces: ['ValidationContractFixtures'], excludedClasses: ['ValidationContractFixtures\\Excluded']),
            $arrays,
            new FieldOrder($paths),
            new MessageCoverage($names, $paths, $expressions, new SizeMessageBranches()),
            $paths,
            $names,
            new Configuration(
                \array_map(static fn (string $class): array => ['class' => 'ValidationContractFixtures\\' . $class, 'fields' => ['items', 'tags']], ['WrongFields', 'OrdinaryAfterRelationship', 'ParentAfterChild', 'SplitRelationship', 'Valid']),
                [['class' => 'ValidationContractFixtures\\InputBound', 'fields' => ['ownerId']], ['class' => 'ValidationContractFixtures\\ActualNestedInput', 'fields' => ['input']]],
            ),
        );
    }

    /**
     * @param list<string> $pairs
     */
    private function assertMissingPairs(string $file, array $pairs): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/validation-contracts/' . $file]);
        $this->assertSame(\array_map(static fn (string $pair): string => 'Provide an explicit public validation message for ' . $pair . '.', $pairs), \array_map(static fn ($error): string => $error->getMessage(), $errors));
    }
}
