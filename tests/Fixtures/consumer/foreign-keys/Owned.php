<?php

declare(strict_types = 1);

namespace ForeignKeyConsumer;

use Illuminate\Database\Schema\Blueprint;

function owned(Blueprint $table): void
{
    $table->foreignId('owner_id')->constrained();
}
