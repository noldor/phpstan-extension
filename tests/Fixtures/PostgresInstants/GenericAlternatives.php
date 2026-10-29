<?php

declare(strict_types = 1);

namespace PostgresInstantFixtures;

use Illuminate\Database\Schema\Blueprint;

function genericAlternatives(Blueprint $table, bool $condition): void
{
    $parameters = $condition ? ['type' => 'date'] : ['type' => 'timestampTz', 'precision' => 6];
    $table->addColumn('timestampTz', 'value', $parameters);
    $parameters = $condition ? ['type' => 'date', 'precision' => 3] : ['type' => 'dateTimeTz', 'precision' => 6];
    $table->addColumn('timestampTz', 'value', $parameters);
    $parameters = $condition ? ['type' => 'timestampTz', 'precision' => 3] : ['type' => 'timestampTz', 'precision' => 6];
    $table->addColumn('timestampTz', 'value', $parameters);
}
