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
use Noldors\PHPStan\Validation\ConstantSource;
use PHPUnit\Framework\Attributes\DataProvider;
use Noldors\PHPStan\Validation\MessageCoverage;
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

        return new LaravelValidationContractRule(
            new ClassScope(namespaces: ['ValidationContractFixtures'], excludedClasses: ['ValidationContractFixtures\\Excluded']),
            $arrays,
            new FieldOrder($paths),
            new MessageCoverage($names, $paths),
            $paths,
            $names,
            new Configuration(
                \array_map(static fn (string $class): array => ['class' => 'ValidationContractFixtures\\' . $class, 'fields' => ['items', 'tags']], ['WrongFields', 'OrdinaryAfterRelationship', 'ParentAfterChild', 'SplitRelationship', 'Valid']),
                [['class' => 'ValidationContractFixtures\\InputBound', 'fields' => ['ownerId']], ['class' => 'ValidationContractFixtures\\ActualNestedInput', 'fields' => ['input']]],
            ),
        );
    }
}
