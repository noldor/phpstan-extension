<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Illuminate\Support\Collection;
use Noldors\PHPStan\Configuration\ClassScope;
use Noldors\PHPStan\Rules\MutableCollectionExposureRule;
use Noldors\PHPStan\Tests\Fixtures\MutableCollections\MutableBag;
use Noldors\PHPStan\Tests\Fixtures\MutableCollections\InvariantOwner;

/**
 * @extends RuleTestCase<MutableCollectionExposureRule>
 */
final class MutableCollectionExposureRuleTest extends RuleTestCase
{
    private const string MESSAGE = 'Do not expose an internal mutable collection from an invariant owner; return a snapshot or a read-only view instead.';

    /**
     * @var list<string>
     */
    private array $ownerClasses = [InvariantOwner::class];

    /**
     * @var list<string>
     */
    private array $ownerNamespaces = [];

    /**
     * @var list<string>
     */
    private array $mutableTypes = [Collection::class, MutableBag::class];

    public function testRejectsDirectAndStraightLineAliasExposureButAllowsSnapshots(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/MutableCollections/Owners.php'], \array_map(
            static fn (int $line): array => [self::MESSAGE, $line],
            [27, 32, 39, 45, 115],
        ));
    }

    public function testRequiresAnExplicitlyConfiguredInvariantOwner(): void
    {
        $this->ownerClasses = [];

        $this->analyse([__DIR__ . '/../Fixtures/MutableCollections/Owners.php'], []);
    }

    public function testChecksOnlyConfiguredCollectionTypes(): void
    {
        $this->mutableTypes = [MutableBag::class];

        $this->analyse([__DIR__ . '/../Fixtures/MutableCollections/Owners.php'], [[self::MESSAGE, 115]]);
    }

    public function testCanSelectOwnersByNamespace(): void
    {
        $this->ownerClasses = [];
        $this->ownerNamespaces = ['Noldors\\PHPStan\\Tests\\Fixtures\\MutableCollections'];

        $this->analyse([__DIR__ . '/../Fixtures/MutableCollections/Owners.php'], \array_map(
            static fn (int $line): array => [self::MESSAGE, $line],
            [27, 32, 39, 45, 115, 127],
        ));
    }

    public function testReportsTheEncapsulationIdentifier(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/MutableCollections/Owners.php']);

        $this->assertNotEmpty($errors);
        $this->assertSame('encapsulation.mutableCollectionExposure', $errors[0]->getIdentifier());
    }

    protected function getRule(): MutableCollectionExposureRule
    {
        return new MutableCollectionExposureRule(
            new ClassScope($this->ownerClasses, $this->ownerNamespaces),
            $this->mutableTypes,
        );
    }
}
