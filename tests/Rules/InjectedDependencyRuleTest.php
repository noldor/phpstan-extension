<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Php\PhpVersion;
use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Configuration\ClassScope;
use Noldors\PHPStan\Rules\InjectedDependencyRule;

/**
 * @extends RuleTestCase<InjectedDependencyRule>
 */
final class InjectedDependencyRuleTest extends RuleTestCase
{
    private int $targetVersion = 80_500;

    public function testDoesNotRequireSyntaxUnavailableInTheAnalysisTarget(): void
    {
        $this->targetVersion = 70_400;
        $this->analyse([__DIR__ . '/../Fixtures/standards/Dependencies.php'], []);
    }

    public function testPhp80RequiresPromotionWithoutReadonly(): void
    {
        $this->targetVersion = 80_000;
        $this->analyse([__DIR__ . '/../Fixtures/standards/Dependencies.php'], [
            ['Promote retained dependency $repository into the constructor.', 9],
        ]);
    }

    public function testChecksRetainedReferencesWithoutRewritingMutableOrHydratedState(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/standards/Dependencies.php']);
        $actual = \array_map(static fn ($error): string => $error->getLine() . ':' . $error->getIdentifier(), $errors);
        \sort($actual);
        $this->assertSame(['16:api.dependencyReadonly', '9:api.dependencyPromotion', '9:api.dependencyReadonly'], $actual);
    }

    public function testPreservesReferencesUnsetAndUnknownDynamicWrites(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/standards/Dependencies.php', __DIR__ . '/../Fixtures/standards/DependencyEdges.php'], [
            ['Promote retained dependency $repository into the constructor.', 9],
            ['Declare the construction-only dependency reference $repository readonly.', 9],
            ['Declare the construction-only dependency reference $repository readonly.', 16],
        ]);
    }

    protected function getRule(): InjectedDependencyRule
    {
        return new InjectedDependencyRule(new ClassScope(namespaces: ['StandardsFixture\\Dependencies']), new PhpVersion($this->targetVersion));
    }
}
