<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\ForeignKeys;

use Illuminate\Database\Schema\Blueprint;

function invalid(Blueprint $table): void
{
    $table->foreignId('owner_id')->constrained();
    $table->foreignId('editor_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('author_id')->constrained('users')->cascadeOnUpdate();
    $table->foreignId('reviewer_id')->constrained('users')->nullOnDelete()->restrictOnUpdate();
    $table->uuid('organization_id');
    $table->foreign('organization_id')->references('id')->on('organizations')->noActionOnDelete()->nullOnUpdate();
    $table->string('tenant_code')->nullable();
    $table->string('project_code');
    $table->foreign(['tenant_code', 'project_code'])->references(['tenant', 'code'])->on('projects')->onDelete('SET NULL')->onUpdate('restrict');
    $table->foreignId('manager_id')->nullable(false)->constrained(table: 'users')->nullOnDelete()->cascadeOnUpdate();
    $table->foreign('legacy_id')->references('id')->cascadeOnDelete()->cascadeOnUpdate();
}
