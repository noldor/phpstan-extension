<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Schema;

use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;

final class ForeignKeyDeclaration
{
    public bool $explicitTable = false;

    public bool $deleteAction = false;

    public bool $updateAction = false;

    public bool $nullOnDelete = false;

    public bool $nullOnUpdate = false;

    /**
     * @param list<string> $columns
     */
    public function __construct(
        public readonly ForeignKeyBlueprint $blueprint,
        public readonly array $columns,
        public readonly int $line,
        public readonly ForeignKeyColumn|null $column = null,
    ) {}

    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(): array
    {
        $errors = [];

        foreach ([
            [$this->explicitTable, 'Declare the foreign key referenced table explicitly.', 'laravel.foreignKeyTable'],
            [$this->deleteAction, 'Declare the foreign key ON DELETE action explicitly.', 'laravel.foreignKeyDelete'],
            [$this->updateAction, 'Declare the foreign key ON UPDATE action explicitly.', 'laravel.foreignKeyUpdate'],
        ] as [$satisfied, $message, $identifier]) {
            if (! $satisfied) {
                $errors[] = RuleErrorBuilder::message($message)->identifier($identifier)->line($this->line)->build();
            }
        }

        if (! $this->nullOnDelete && ! $this->nullOnUpdate) {
            return $errors;
        }

        if (null !== $this->column && null === $this->column->name && false === $this->column->nullable) {
            $errors[] = RuleErrorBuilder::message('SET NULL requires nullable foreign key columns.')
                ->identifier('laravel.foreignKeyNullable')->line($this->line)->build();
        }

        foreach ($this->columns as $name) {
            $column = $this->blueprint->columns[$name] ?? null;

            if (null !== $column && false === $column->nullable) {
                $errors[] = RuleErrorBuilder::message('SET NULL requires nullable foreign key column "' . $name . '".')
                    ->identifier('laravel.foreignKeyNullable')->line($this->line)->build();
            }
        }

        return $errors;
    }
}
