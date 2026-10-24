<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use PHPStan\Node\MethodCallableNode;
use PHPStan\Node\FunctionCallableNode;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Node\InstantiationCallableNode;
use PHPUnit\Framework\Attributes\DataProvider;
use Noldors\PHPStan\Rules\LaravelValidationCallableRule;
use Noldors\PHPStan\Rules\LaravelValidationLocationRule;

/**
 * @extends RuleTestCase<LaravelValidationCallableRule>
 */
final class LaravelValidationCallableRuleTest extends RuleTestCase
{
    /**
     * @var class-string<FunctionCallableNode|StaticMethodCallableNode|MethodCallableNode|InstantiationCallableNode>
     */
    private string $nodeType = FunctionCallableNode::class;

    /**
     * @param class-string<FunctionCallableNode|StaticMethodCallableNode|MethodCallableNode|InstantiationCallableNode> $nodeType
     * @param list<int> $lines
     */
    #[DataProvider('callableTypes')]
    public function testRejectsReferencesToValidationCreation(string $nodeType, array $lines): void
    {
        $this->nodeType = $nodeType;
        $this->analyse([__DIR__ . '/../Fixtures/app/UseCases/MoreInlineValidation.php'], \array_map(
            static fn (int $line): array => ['Define Laravel validation only in validator classes under the configured allowed paths; invoke a prepared validator here instead.', $line],
            $lines,
        ));
    }

    /**
     * @param class-string<FunctionCallableNode|StaticMethodCallableNode|MethodCallableNode|InstantiationCallableNode> $nodeType
     * @param list<int> $lines
     */
    #[DataProvider('callableTypes')]
    public function testAllowsReferencesInsideAValidatorClass(string $nodeType, array $lines): void
    {
        $this->nodeType = $nodeType;
        $this->analyse([__DIR__ . '/../Fixtures/app/Validation/CallableAllowed.php'], []);
    }

    /**
     * @param class-string<FunctionCallableNode|StaticMethodCallableNode|MethodCallableNode|InstantiationCallableNode> $nodeType
     * @param list<int> $lines
     */
    #[DataProvider('callableTypes')]
    public function testAllowsReferencesToPreparedValidatorsAndOtherApis(string $nodeType, array $lines): void
    {
        $this->nodeType = $nodeType;
        $this->analyse([__DIR__ . '/../Fixtures/app/UseCases/PreparedValidation.php'], []);
    }

    /**
     * @return iterable<string, array{class-string<FunctionCallableNode|StaticMethodCallableNode|MethodCallableNode|InstantiationCallableNode>, list<int>}>
     */
    public static function callableTypes(): iterable
    {
        yield 'function' => [FunctionCallableNode::class, [30]];

        yield 'static method' => [StaticMethodCallableNode::class, [22, 29]];

        yield 'method' => [MethodCallableNode::class, [23]];

        yield 'constructor' => [InstantiationCallableNode::class, [32]];
    }

    public function testRejectsReferencesToRuleBuilderMutations(): void
    {
        $this->nodeType = MethodCallableNode::class;
        $this->analyse([__DIR__ . '/../Fixtures/app/UseCases/RuleBuilders.php'], [[
            'Define Laravel validation only in validator classes under the configured allowed paths; invoke a prepared validator here instead.',
            19,
        ]]);
    }

    public function testAllowsStaticPredicateReferencesButRejectsRuleFactoryReferences(): void
    {
        $this->nodeType = StaticMethodCallableNode::class;
        $this->analyse([__DIR__ . '/../Fixtures/app/UseCases/StaticRuleMethods.php'], \array_map(
            static fn (int $line): array => ['Define Laravel validation only in validator classes under the configured allowed paths; invoke a prepared validator here instead.', $line],
            [19, 23, 24, 26, 28],
        ));
    }

    protected function getRule(): LaravelValidationCallableRule
    {
        return new LaravelValidationCallableRule(
            new LaravelValidationLocationRule(
                self::getFileHelper(),
                self::createReflectionProvider(),
                [
                    __DIR__ . '/../Fixtures/app/GraphQL/Validators',
                    __DIR__ . '/../Fixtures/app/Http/Requests',
                    __DIR__ . '/../Fixtures/app/Validation',
                ],
            ),
            $this->nodeType,
        );
    }
}
