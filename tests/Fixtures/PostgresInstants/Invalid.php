<?php

declare(strict_types = 1);

namespace PostgresInstantFixtures;

use Illuminate\Database\Schema\Blueprint;

/**
 * @param list<mixed> $arguments
 */
function invalid(Blueprint $table, int $precision, bool $condition, array $arguments): void
{
    $table->timestamp('occurred_at', 6);
    $table->dateTime('occurred_at', 6);
    $table->timestamps(6);
    $table->nullableTimestamps(6);
    $table->datetimes(6);
    $table->softDeletes('deleted_at', 6);
    $table->softDeletesDatetime('deleted_at', 6);
    $table->timestampTz('occurred_at');
    $table->timestampTz('occurred_at', 0);
    $table->timestampTz('occurred_at', 3);
    $table->timestampTz('occurred_at', 5);
    $table->timestampTz('occurred_at', 7);
    $table->timestampTz('occurred_at', null);
    $table->timestampTz('occurred_at', $precision);
    $table->timestampTz('occurred_at', $condition ? 6 : 3);
    $table->timestampTz(column: 'occurred_at', precision: 0);
    $table->timestampTz(precision: 3, column: 'occurred_at');
    $table->dateTimeTz('occurred_at');
    $table->dateTimeTz('occurred_at', 3)->nullable()->change();
    $table->timestampsTz();
    $table->timestampsTz(0);
    $table->nullableTimestampsTz(3);
    $table->softDeletesTz();
    $table->softDeletesTz(precision: 3);
    $table->TIMESTAMPTZ('occurred_at', 3);
    $table->timestampTz(...$arguments);
    $table->timestampTz(...['occurred_at', 3]);
    $method = 'timestamp';
    $table->$method('occurred_at', 6);
    $method = $condition ? 'timestampTz' : 'timestamp';
    $table->$method('occurred_at', 6);
}
