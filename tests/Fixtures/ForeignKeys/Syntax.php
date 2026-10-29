<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\ForeignKeys;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

const USERS = 'users';

Schema::create('projects', function (Blueprint $table): void {
    $table->foreignId('owner_id')->constrained(USERS)->restrictOnDelete()->noActionOnUpdate();
});
Schema::table('projects', fn (Blueprint $table) => $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete());

function syntax(Blueprint $table, bool $condition): void
{
    $target = 'users';
    $action = 'set null';
    $method = 'CONSTRAINED';
    $table->foreignId(...['owner_id'])->nullable()->{$method}(...['table' => $target])->onDelete($action)->onUpdate('no action');
    $table->addColumn('bigInteger', 'author_id', ['nullable' => true]);
    $table->foreign('author_id')->references('id')->on($target)->nullOnDelete()->restrictOnUpdate();
    $table->addColumn('bigInteger', 'manager_id', ['name' => 'actual_id', 'nullable' => true]);
    $table->foreign('actual_id')->references('id')->on($target)->nullOnDelete()->restrictOnUpdate();
    $table->softDeletes();
    $table->foreign('deleted_at')->references('id')->on('history')->nullOnDelete()->restrictOnUpdate();
    $table->id();
    $table->foreign('id')->references('id')->on($target)->nullOnDelete()->cascadeOnUpdate();
    $table->addColumn('bigInteger', 'generic_id', ['nullable' => false]);
    $table->foreign('generic_id')->references('id')->on($target)->nullOnDelete()->cascadeOnUpdate();
    $column = $table->foreignId('conditional_id');
    if ($condition) {
        $column->nullable();
    }
    $column->constrained($target)->nullOnDelete()->restrictOnUpdate();
    $table->foreignId('positional_id')->nullable()->constrained(...[$target])->nullOnDelete()->cascadeOnUpdate();
}
