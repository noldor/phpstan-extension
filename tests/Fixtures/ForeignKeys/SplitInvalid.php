<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\ForeignKeys;

use Illuminate\Database\Schema\Blueprint;

function splitInvalid(Blueprint $table): void
{
    $column = $table->foreignId('owner_id');
    $fk = $column->constrained(table: 'users');
    $alias = $fk;
    $alias->nullOnDelete();
    $column->nullable(false);
    $fk->cascadeOnUpdate();
    $table->foreignId('editor_id')->constrained('users')->nullable()->nullOnDelete()->cascadeOnUpdate();
    $table->uuid('project_id')->nullable();
    $composite = $table->foreign(['project_id', 'tenant_id']);
    $composite->references(['id', 'tenant'])->on('projects')->nullOnDelete()->restrictOnUpdate();
    $table->uuid('tenant_id');
}
