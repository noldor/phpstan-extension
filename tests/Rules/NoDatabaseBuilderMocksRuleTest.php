<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Illuminate\Database\Query\Builder;
use Noldors\PHPStan\Configuration\ClassScope;
use Illuminate\Database\Eloquent\Relations\Relation;
use Noldors\PHPStan\Rules\NoDatabaseBuilderMocksRule;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @extends RuleTestCase<NoDatabaseBuilderMocksRule>
 */
final class NoDatabaseBuilderMocksRuleTest extends RuleTestCase
{
    private const string MESSAGE = 'Exercise database builders and relationships against an isolated test database instead of mocking or stubbing them.';

    /**
     * @var list<string>
     */
    private array $testNamespaces = ['DatabaseBuilderMocks\\PersistenceTests'];

    /**
     * @var list<string>
     */
    private array $excludedClasses = [];

    /**
     * @var list<string>
     */
    private array $forbiddenTypes = [Builder::class, EloquentBuilder::class, Relation::class];

    /**
     * @return list<string>
     */
    public static function getAdditionalConfigFiles(): array
    {
        return [__DIR__ . '/../Fixtures/DatabaseBuilderMocks/phpstan.neon'];
    }

    public function testRejectsKnownPhpunitMockAndStubTargets(): void
    {
        $this->analyseFixture('Phpunit.php', [19, 20, 21, 22, 23, 24, 26, 27, 28, 29, 35, 56]);
    }

    public function testRejectsKnownMockeryAndLaravelTargetsWithoutGuessingMethodNames(): void
    {
        $this->analyseFixture('MockeryAndLaravel.php', [19, 20, 21, 23, 24, 37, 38, 39, 40]);
    }

    public function testRejectsConfiguredDoublesAndStubBuilders(): void
    {
        $this->analyseFixture('ConfiguredPhpunit.php', [15, 16, 17]);
    }

    public function testEmptyScopePreservesExistingTests(): void
    {
        $this->testNamespaces = [];

        $this->analyseFixture('Phpunit.php', []);
        $this->analyseFixture('MockeryAndLaravel.php', []);
        $this->analyseFixture('ConfiguredPhpunit.php', []);
    }

    public function testConfiguredExclusionsRemainAllowed(): void
    {
        $this->excludedClasses = ['DatabaseBuilderMocks\\PersistenceTests\\PhpunitCalls'];

        $this->analyseFixture('Phpunit.php', []);
    }

    public function testOnlyConfiguredDatabaseTypesAreRestricted(): void
    {
        $this->forbiddenTypes = [Relation::class];

        $this->analyseFixture('Phpunit.php', [22]);
    }

    public function testReportsTheDatabaseBuilderMockIdentifier(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/DatabaseBuilderMocks/Phpunit.php']);

        $this->assertNotEmpty($errors);
        $this->assertSame('phpunit.databaseBuilderMock', $errors[0]->getIdentifier());
    }

    protected function getRule(): NoDatabaseBuilderMocksRule
    {
        return new NoDatabaseBuilderMocksRule(
            new ClassScope(namespaces: $this->testNamespaces, excludedClasses: $this->excludedClasses),
            self::createReflectionProvider(),
            $this->forbiddenTypes,
        );
    }

    /**
     * @param list<int> $lines
     */
    private function analyseFixture(string $fixture, array $lines): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/DatabaseBuilderMocks/' . $fixture], \array_map(
            static fn (int $line): array => [self::MESSAGE, $line],
            $lines,
        ));
    }
}
