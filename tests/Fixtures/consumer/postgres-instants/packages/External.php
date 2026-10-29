<?php

declare(strict_types = 1);

namespace ConsumerPostgresInstants\Packages;

use Illuminate\Database\Schema\Blueprint;

function external(Blueprint $table): void
{
    $table->timestamps();
}
