<?php

declare(strict_types = 1);

namespace PostgresInstantFixtures;

use Illuminate\Database\Schema\Blueprint;

function nullsafeDeclarations(Blueprint|null $table): void
{
    $table?->timestamp('occurred_at', 6);
    $table?->timestampTz('occurred_at', 3);
    $table?->timestampTz('occurred_at', 6);
}
