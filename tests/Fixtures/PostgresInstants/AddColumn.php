<?php

declare(strict_types = 1);

namespace PostgresInstantFixtures;

use Illuminate\Database\Schema\Blueprint;

/**
 * @param array<string, mixed> $parameters
 */
function addColumns(Blueprint $table, array $parameters): void
{
    $table->addColumn('timestamp', 'occurred_at', ['precision' => 6]);
    $table->addColumn('dateTime', 'occurred_at', ['precision' => 6]);
    $table->addColumn('timestampTz', 'occurred_at');
    $table->addColumn('dateTimeTz', 'occurred_at', ['precision' => 3]);
    $table->addColumn('timestampTz', 'occurred_at', $parameters);
    $table->addColumn(parameters: ['precision' => 0], name: 'occurred_at', type: 'timestampTz');

    $table->addColumn('timestampTz', 'occurred_at', ['precision' => 6]);
    $table->addColumn(type: 'dateTimeTz', name: 'occurred_at', parameters: ['precision' => 6]);
    $table->addColumn('date', 'birthday');
    $table->addColumn('bigInteger', 'unix_seconds');
}
