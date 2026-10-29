<?php

declare(strict_types = 1);

namespace ConsumerPostgresInstants\OtherEngine;

use Illuminate\Database\Schema\Blueprint;

function otherEngine(Blueprint $table): void
{
    $table->timestamps();
}
