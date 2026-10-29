<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\ForeignKeys;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

final class Owner extends Model {}

function inferredColumns(Blueprint $table, string $columnName): void
{
    $table->foreignIdFor(Owner::class)->constrained()->restrictOnDelete()->cascadeOnUpdate();
    $table->foreignIdFor(Owner::class)->constrained('owners')->nullOnDelete()->cascadeOnUpdate();
    $table->foreignId($columnName)->constrained('owners')->nullOnDelete()->cascadeOnUpdate();
    $table->foreignIdFor(Owner::class)->nullable()->constrained('owners')->nullOnDelete()->cascadeOnUpdate();
    $table->foreignIdFor(Owner::class)->table('owners')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
}
