<?php

declare(strict_types = 1);

namespace PostgresInstantFixtures;

use Illuminate\Database\Schema\Blueprint;

const PRECISION = 6;

class CustomBlueprint extends Blueprint {}

class UnrelatedSchema
{
    public function timestamp(string $column): void {}
}

/**
 * @param 6 $knownPrecision
 * @param list<mixed> $arguments
 */
function valid(Blueprint $table, CustomBlueprint $custom, UnrelatedSchema $other, int $knownPrecision, array $arguments): void
{
    $table->timestampTz('occurred_at', 6);
    $table->dateTimeTz('occurred_at', 6);
    $table->timestampsTz(6);
    $table->nullableTimestampsTz(6);
    $table->softDeletesTz('deleted_at', 6);
    $table->softDeletesTz(precision: 6);
    $table->timestampTz(precision: 6, column: 'occurred_at')->nullable()->change();
    $table->dateTimeTz(column: 'occurred_at', precision: PRECISION);
    $table->timestampsTz(precision: $knownPrecision);
    $custom->timestampTz('occurred_at', 6);
    $table->date('birthday');
    $table->time('opens_at');
    $table->timeTz('opens_at', 6);
    $table->integer('unix_seconds');
    $table->bigInteger('unix_microseconds');
    $table->dropTimestamps();
    $table->dropTimestampsTz();
    $table->dropSoftDeletes();
    $table->timestampTz(...);
    $table->timestamp(...);
    $table->timestampTz(...['occurred_at', 6]);
    $table->timestampTz(...['column' => 'occurred_at', 'precision' => 6]);
    $table->timestampTz(...$arguments, precision: 6);
    $method = 'timestampTz';
    $table->$method('occurred_at', 6);
    $other->timestamp('custom');
}
