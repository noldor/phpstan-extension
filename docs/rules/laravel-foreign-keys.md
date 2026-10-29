# Explicit foreign-key declarations

The Laravel set checks application-owned Schema Builder declarations selected by
`noldors.foreignKeys.paths`. The selection is empty by default. Directory paths
include descendants; file paths select one migration. Paths are resolved from the
PHPStan working directory. Exclusions take precedence.

```neon
includes:
    - vendor/noldors/phpstan-extension/src/laravel.neon

parameters:
    noldors:
        foreignKeys:
            paths:
                - database/migrations
            excludePaths:
                - database/migrations/vendor
```

The rule reports four independent contracts:

| Identifier | Requirement |
| --- | --- |
| `laravel.foreignKeyTable` | Declare the referenced table explicitly with `constrained('users')`, `table('users')->constrained()` or `references(...)->on('users')`. A model-inferred table from `foreignIdFor()` is not an explicit table declaration. |
| `laravel.foreignKeyDelete` | Declare an ON DELETE action with `onDelete(...)` or Laravel's corresponding action helper. |
| `laravel.foreignKeyUpdate` | Declare an ON UPDATE action with `onUpdate(...)` or Laravel's corresponding action helper. |
| `laravel.foreignKeyNullable` | Every locally declared column of a SET NULL foreign key must be nullable. Composite foreign keys check every known constituent column. |

Cascade, restrict, no action and set null are accepted explicit choices. The rule
does not select lifecycle behavior or require a foreign key for every `*_id`
column. Polymorphic relationships do not acquire a conventional foreign key.

```php
$table->foreignId('owner_id')
    ->constrained('users')
    ->restrictOnDelete()
    ->cascadeOnUpdate();

$table->foreignUuid('reviewer_id')
    ->nullable()
    ->constrained('users')
    ->nullOnDelete()
    ->noActionOnUpdate();

$table->string('tenant_code')->nullable();
$table->string('project_code')->nullable();
$table->foreign(['tenant_code', 'project_code'])
    ->references(['tenant', 'code'])
    ->on('projects')
    ->nullOnDelete()
    ->restrictOnUpdate();
```

Analysis follows local Blueprint, column and foreign-key variable aliases and
split method chains within a function, method, closure or arrow function. It
recognizes typed Blueprint parameters and local `new Blueprint` instances,
import aliases, constant method names, named arguments and known argument arrays.
Nullable modifiers must affect the column: `nullable()` after `constrained()`
modifies the returned foreign-key definition, so it does not make the column
nullable.

Nullability comes from local column declarations, including generic `addColumn`
parameters and nullable Laravel column helpers. A column declared later in the
same body is still checked. Unknown prior schema state is never treated as proof
that a column is nonnullable. Conditional nullable changes are treated as unknown;
this is local declaration analysis, not exhaustive execution-path analysis.
It does not execute custom Schema macros/helpers, interpret raw SQL, reconstruct
migration history or prove the lifecycle justification for SET NULL. Keep the
corresponding database migration and lifecycle tests in the consuming project.

Declarations are diagnostics only. Evolve deployed constraints with new forward
migrations rather than rewriting applied migration history.
