<?php

declare(strict_types = 1);

namespace PostgresInstantFixtures;

use Illuminate\Database\Schema\Blueprint;

function genericBoundaries(Blueprint $table): void
{
    $table->addColumn('TIMESTAMP', 'occurred_at', ['precision' => 6]);
    $table->addColumn('DATETIMETZ', 'occurred_at', ['precision' => 3]);
    $table->addColumn('timestampTz', 'occurred_at', ['type' => 'timestamp', 'precision' => 6]);
    $table->addColumn('string', 'occurred_at', ['type' => 'timestamp', 'precision' => 6]);
    $table->addColumn('timestamp', 'occurred_at', ['type' => 'dateTimeTz', 'precision' => 3]);

    $table->addColumn('timestamp', 'occurred_at', ['type' => 'timestampTz', 'precision' => 6]);
    $table->addColumn('string', 'occurred_at', ['type' => 'timestampTz', 'precision' => 6]);
    $table->addColumn('TIMESTAMPTZ', 'occurred_at', ['precision' => 6]);
    $table->addColumn('timestamp', 'birthday', ['type' => 'date']);
}
