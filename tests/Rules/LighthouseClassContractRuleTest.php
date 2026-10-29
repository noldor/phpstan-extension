<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Configuration\ClassScope;
use Noldors\PHPStan\Rules\LighthouseClassContractRule;

/**
 * @extends RuleTestCase<LighthouseClassContractRule>
 */
final class LighthouseClassContractRuleTest extends RuleTestCase
{
    public function testChecksOnlyExplicitlySelectedRolesAndAllowsInheritedInvoke(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/standards/Lighthouse.php']);
        $actual = \array_map(static fn ($error): string => $error->getIdentifier() ?? 'missing.identifier', $errors);
        \sort($actual);
        $this->assertSame([
            'lighthouse.resolverInvokable', 'lighthouse.subscriptionFinal',
            'lighthouse.validatorBase', 'lighthouse.validatorBase', 'lighthouse.validatorFinal',
        ], $actual);
    }

    protected function getRule(): LighthouseClassContractRule
    {
        $prefix = 'StandardsFixture\\Lighthouse\\';

        return new LighthouseClassContractRule(
            new ClassScope(classes: [$prefix . 'CreateValidator', $prefix . 'WrongValidator', $prefix . 'UpdateValidator', $prefix . 'ValidatorBase', $prefix . 'WrongValidatorBase']),
            new ClassScope(classes: [$prefix . 'MissingHandler', $prefix . 'InheritedHandler']),
            new ClassScope(classes: [$prefix . 'ChangedSubscription', $prefix . 'CreatedSubscription']),
        );
    }
}
