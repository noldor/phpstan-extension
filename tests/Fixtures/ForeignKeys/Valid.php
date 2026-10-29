<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\ForeignKeys;

use Illuminate\Database\Schema\Blueprint as Table;

function valid(Table $table): void
{
    $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
    $table->foreignUuid('editor_id')->nullable()->constrained(table: 'users')->nullOnDelete()->nullOnUpdate();
    $table->foreignUlid('author_id')->constrained('users')->restrictOnDelete()->noActionOnUpdate();
    $table->string('tenant_code')->nullable();
    $table->string('project_code')->nullable();
    $table->foreign(columns: ['tenant_code', 'project_code'])->references(['tenant', 'code'])->on(table: 'projects')->onDelete(action: 'set null')->onUpdate(action: 'no action');
    // Prior schema state is unknown; a missing local declaration is not a non-nullable column.
    $table->foreign('existing_id')->references('id')->on('users')->nullOnDelete()->nullOnUpdate();
    // A foreign-looking name or polymorphic relationship does not require an FK.
    $table->foreignId('unconstrained_id');
    $table->nullableMorphs('subject');
}

function split(Table $table): void
{
    $blueprint = $table;
    $column = $blueprint->foreignId('owner_id');
    $alias = $column;
    $column->nullable();
    $foreign = $alias->constrained('users');
    $fk = $foreign;
    $foreign->nullOnDelete();
    $fk->restrictOnUpdate();
    // constrained() returns a foreign-key definition; nullable() here does not change its column.
    $table->foreignId('ignored_id');
}

final class OtherBuilder
{
    public function foreignId(string $name): self { return $this; }
    public function constrained(): self { return $this; }
}

function unrelated(OtherBuilder $table): void
{
    $table->foreignId('owner_id')->constrained();
}
