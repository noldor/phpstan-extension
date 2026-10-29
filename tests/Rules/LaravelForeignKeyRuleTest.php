<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PHPStan\Testing\RuleTestCase;
use Noldors\PHPStan\Rules\LaravelForeignKeyRule;

/**
 * @extends RuleTestCase<LaravelForeignKeyRule>
 */
final class LaravelForeignKeyRuleTest extends RuleTestCase
{
    public function testRequiresExplicitTargetAndBothReferentialActions(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/ForeignKeys/Invalid.php'], [
            ['Declare the foreign key referenced table explicitly.', 11],
            ['Declare the foreign key ON DELETE action explicitly.', 11],
            ['Declare the foreign key ON UPDATE action explicitly.', 11],
            ['Declare the foreign key ON UPDATE action explicitly.', 12],
            ['Declare the foreign key ON DELETE action explicitly.', 13],
            ['SET NULL requires nullable foreign key column "reviewer_id".', 14],
            ['SET NULL requires nullable foreign key column "organization_id".', 16],
            ['SET NULL requires nullable foreign key column "project_code".', 19],
            ['SET NULL requires nullable foreign key column "manager_id".', 20],
            ['Declare the foreign key referenced table explicitly.', 21],
        ]);
    }

    public function testAcceptsDeclaredActionsNullableKeysAliasesAndUnknownPriorColumns(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/ForeignKeys/Valid.php'], []);
    }

    public function testFollowsSplitChainsAndChecksTheEffectiveColumnDefinition(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/ForeignKeys/SplitInvalid.php'], [
            ['SET NULL requires nullable foreign key column "owner_id".', 12],
            ['SET NULL requires nullable foreign key column "editor_id".', 17],
            ['SET NULL requires nullable foreign key column "tenant_id".', 19],
        ]);
    }

    public function testHandlesClosuresConstantsDynamicNamesAndGenericNullableDefinitions(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/ForeignKeys/Syntax.php'], [
            ['Declare the foreign key ON UPDATE action explicitly.', 15],
            ['SET NULL requires nullable foreign key column "id".', 30],
            ['SET NULL requires nullable foreign key column "generic_id".', 32],
        ]);
    }

    public function testRequiresExplicitTablesForModelColumnsAndChecksUnnamedColumnNullability(): void
    {
        $this->analyse([__DIR__ . '/../Fixtures/ForeignKeys/InferredColumns.php'], [
            ['Declare the foreign key referenced table explicitly.', 14],
            ['SET NULL requires nullable foreign key columns.', 15],
            ['SET NULL requires nullable foreign key columns.', 16],
        ]);
    }

    public function testPublishesStableDiagnosticIdentifiers(): void
    {
        $errors = $this->gatherAnalyserErrors([__DIR__ . '/../Fixtures/ForeignKeys/Invalid.php']);
        $this->assertSame([
            'laravel.foreignKeyTable',
            'laravel.foreignKeyDelete',
            'laravel.foreignKeyUpdate',
            'laravel.foreignKeyUpdate',
            'laravel.foreignKeyDelete',
            'laravel.foreignKeyNullable',
            'laravel.foreignKeyNullable',
            'laravel.foreignKeyNullable',
            'laravel.foreignKeyNullable',
            'laravel.foreignKeyTable',
        ], \array_map(static fn ($error): string|null => $error->getIdentifier(), $errors));
    }

    protected function getRule(): LaravelForeignKeyRule
    {
        return new LaravelForeignKeyRule(self::getFileHelper(), self::createReflectionProvider(), [__DIR__ . '/../Fixtures/ForeignKeys'], []);
    }
}
