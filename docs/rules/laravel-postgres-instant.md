# Application-owned PostgreSQL instants

`LaravelPostgresInstantRule` requires `timestamp(6) with time zone` for Laravel
Schema Builder instant declarations in explicitly selected application-owned
PostgreSQL files. It reports `laravel.postgresqlInstant`.

## Connection and scope

Include the Laravel set and select the files or directories that own PostgreSQL
instant columns:

```neon
includes:
    - vendor/noldors/phpstan-extension/src/laravel.neon

parameters:
    paths:
        - app
        - database/migrations
    noldors:
        postgresqlInstants:
            paths:
                - database/migrations
            excludePaths:
                - database/migrations/packages
                - database/migrations/2025_01_01_000000_create_legacy_events.php
```

Both lists default to empty. An empty `postgresqlInstants.paths` disables the
check. The PHP set does not register this rule; the legacy `src/rules.neon` entry
point includes it through the Laravel set.

Paths are individual files or directory roots, absolute or relative to the
directory from which PHPStan runs. Nested directories match; similarly named
neighbouring directories do not. Exclusions take precedence. Lists are validated
as lists of strings. Use `paths!` or `excludePaths!` to replace an inherited list
instead of appending to it.

This scope declares both PostgreSQL applicability and application ownership. The
rule does not boot Laravel, read environment variables, inspect connection
names, or infer ownership from table or column names. Keep other engines,
framework/package schemas and deployed historical migrations outside this scope
or explicitly exclude their files. Published package migrations copied into an
application directory still need explicit exclusions. Shared helper files can
be included when they only declare application-owned PostgreSQL schemas.

## Accepted declarations

```php
$table->timestampTz('occurred_at', 6);
$table->dateTimeTz('received_at', 6);
$table->timestampsTz(6);
$table->nullableTimestampsTz(6);
$table->softDeletesTz(precision: 6);
$table->timestampTz(precision: 6, column: 'processed_at')->nullable()->change();
$table->addColumn('timestampTz', 'published_at', ['precision' => 6]);
```

`dateTimeTz(..., 6)` is accepted because Laravel's PostgreSQL grammar compiles it
to the same `timestamp(6) with time zone` type. Calendar dates, times of day and
integer epochs retain their own semantics:

```php
$table->date('birthday');
$table->time('opens_at');
$table->bigInteger('unix_seconds');
```

The receiver must be statically known as
`Illuminate\Database\Schema\Blueprint` or a subclass. Named arguments,
constant precision expressions and statically known positional/named argument
unpacking are supported. A precision value must be provably the integer `6`.
An omitted, `null`, dynamic or partly non-six precision is rejected, including
when a runtime default might currently be six. Explicitly supplying
`precision: 6` after an unknown unpack is accepted.

## Rejected declarations

```php
$table->timestamp('occurred_at', 6); // No time zone.
$table->dateTime('occurred_at', 6); // No time zone on PostgreSQL.
$table->timestamps(6);
$table->nullableTimestamps(6);
$table->datetimes(6);
$table->softDeletes(precision: 6);
$table->softDeletesDatetime(precision: 6);
$table->timestampTz('occurred_at'); // Precision is implicit.
$table->timestampsTz(3); // Millisecond precision.
$table->softDeletesTz(); // Precision is implicit.
$table->addColumn('timestamp', 'occurred_at', ['precision' => 6]);
```

Both `up()` and `down()` declarations and `change()` chains are checked when
their files are selected. Column drops and first-class callable creation do not
declare a temporal type and are accepted. Finite, statically known dynamic
method names are checked; unresolved method names are not guessed.

## Boundaries and existing data

This checks direct Schema Builder declarations, including `addColumn()` with
known `timestamp`, `dateTime`, `timestampTz` and `dateTimeTz` types. Generic type
names follow Laravel's case-insensitive dispatch; a `type` entry in the
parameters array overrides the first argument, as it does in Laravel. Known
parameter-array alternatives keep their type and precision together. It does not
parse raw SQL, inspect the live database, follow wrapper/macro bodies outside
the selected scope, or track later mutation of a returned column definition.
Unknown `addColumn()` types and untyped receivers cannot be classified. A
successful analysis is not a schema audit.

The selected timestamp/datetime declarations are assumed to represent instants.
Use a narrowly targeted identifier suppression for an intentional local civil
datetime exception or a mixed-ownership file; document the reason rather than
converting values with different semantics.

For deployed columns, create a new forward migration. Preserve historical
instants and explicitly establish how timezone-naive values are interpreted
before conversion. A precision-reducing rollback can discard microseconds and
must document that limitation. This rule neither rewrites migration history nor
performs data conversion.

The package tests this rule and the emitted PostgreSQL DDL against its installed
Laravel development dependency. Keep the consuming application's Larastan
configuration for framework type inference.

See [Laravel migration column types](https://laravel.com/docs/13.x/migrations#available-column-types)
and [PHPStan custom rules](https://phpstan.org/developing-extensions/rules).
