# Eloquent model contracts

The Eloquent checks are opt-in through `noldors.eloquent.modelClasses` or
`modelNamespaces`. The complete suite targets Laravel **13.38+**, PHPStan 2.3+
and Larastan **3.13.2+ / 3.x**. Older Laravel applications can keep using the
existing Laravel rules without selecting Eloquent models.

Use PHPStan rules for local declarations and collectors for cross-file observer,
morph-map and date-serializer contracts. Collectors participate in PHPStan's
parallel analysis and result cache; no rule shares mutable state between workers.
See [PHPStan collectors](https://phpstan.org/developing-extensions/collectors).

## Connection and scope

```neon
includes:
    - vendor/larastan/larastan/extension.neon
    - vendor/noldors/phpstan-extension/src/laravel-model-properties.neon

parameters:
    level: max
    paths:
        - app
        - database/factories
    databaseMigrationsPath:
        - database/migrations
    noldors:
        eloquent:
            modelNamespaces: [App\Models]
            modelClasses: []
            excludeModels: []
            observerNamespaces: [App\Observers]
            observerClasses: []
            morphTypeColumns: [subject_type, commentable_type]
```

The property bridge includes `laravel.neon` and enables Larastan's
`parseModelCastsMethod`. Install and include Larastan separately; it is not a
runtime dependency of this extension. With only `src/laravel.neon`, the
configuration, date, observer, morph and pivot checks work without Larastan;
the computed-accessor and PHPDoc checks require the bridge. `src/php.neon`
does not register Eloquent checks. All selectors default to empty lists.

Class selectors include subclasses; namespace selectors match namespace
boundaries. Exclusions also include subclasses. Abstract models do not need
individual date attributes or morph-map entries. Selected concrete pivots do.
Observer selectors cover observers whose registrations cannot identify them.
Include model bases, traits, observers and registration providers in **analysis
paths**. `scanFiles` alone does not run collectors over those files.

## Rules

| Identifier | Contract |
| --- | --- |
| `laravel.observerFinal` | Registered/selected application observer classes are `final`. Registrations include `ObservedBy`, `Model::observe()` and `EventServiceProvider::$observers`. No class-name suffix heuristic is used. |
| `laravel.modelCasts` | Static `$casts` declarations move to `casts()`, preserving parent/package and dynamic configuration. |
| `laravel.modelConfiguration` | Applicable static model settings use supported native attributes. See the [attribute catalog](../laravel-attributes.md). |
| `laravel.modelDateFormat` | Each concrete model explicitly declares the required `DateFormat`; legacy overrides, storage-hook overrides and direct constructor changes cannot invalidate it. |
| `laravel.modelJsonDateFormat` | The effective JSON serializer and known casts preserve the required format. Default Carbon JSON with `Z` is insufficient. |
| `laravel.computedAttribute` | Non-column read-only accessors use `Attribute::make(get: ...)` and `Attribute<T, never>`. Writable and database-backed accessors retain their semantics. |
| `laravel.modelPropertyReadOnly` | Documented relationships, computed read-only properties and identifiers use `@property-read`, with explicit writable exceptions. |
| `laravel.modelPropertyOrder` | PHPDoc groups database fields, computed/query attributes, then relationships, including trait relationships. Cast/accessor-backed columns stay in the database group. |
| `laravel.modelPropertyName` | PHPDoc names resolve to independent columns, accessors, relations or configured query-derived properties. |
| `laravel.modelPropertyType` | Readable types agree with schema/cast/accessor/relation metadata, including reliable nullability and generic/shape contracts. Narrow array shapes may refine a broad `array` cast. |
| `laravel.modelSchema` | Independent schema metadata is missing for a documented model. Configure Larastan's migrations/schema; dynamic table/connection models require manual verification. |
| `laravel.morphMap` | Every concrete selected model has a declared entry in `Relation::morphMap()` or `enforceMorphMap()`. Namespace selection automatically includes future models. |
| `laravel.morphMapUnverifiable` | A dynamic morph map cannot establish static registration. |
| `laravel.morphAlias` | Known alias literals in configured discriminator contexts use `Relation::getMorphAlias(Model::class)`. Registration and migration literals stay stable. |
| `laravel.pivotClass` | Each owned many-to-many direction selects a custom `Pivot`, or `MorphPivot` for morph relations. |
| `laravel.pivotChaperone` | Both explicit inverse names are configured after pivot selection and remain effective on the returned relationship. |
| `laravel.pivotInverse` | Pivot inverse methods are `belongsTo`/`morphTo` relations whose declared related types cover the models in the current direction. |
| `laravel.pivotUnverifiable` | The effective returned pivot configuration cannot be established. |

## Database and JSON date formats

Both defaults are `Y-m-d\TH:i:s.uP`. They preserve six fractional digits and a
numeric offset such as `+03:00` or `+00:00`:

```php
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\DateFormat;

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class Event extends ApplicationModel
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\\TH:i:s.uP');
    }
}
```

The serializer may be inherited or supplied by a trait. Every reachable return
must directly call `format()` on a `DateTimeInterface` with the required constant
format; aliases/constants resolved by PHPStan are supported. Indirect serializers
need a reviewed, explicit trust declaration. Native `date:FORMAT`,
`datetime:FORMAT` and immutable variants bypass `serializeDate()` and must use
the JSON format too. Custom `SerializesCastableAttributes` implementations need
an explicit property exception after their contract has been verified. Dynamic
or unresolved `casts()` results do not establish the JSON contract.

```neon
parameters:
    noldors:
        eloquent:
            databaseDateFormat: 'Y-m-d\TH:i:s.uP'
            jsonDateFormat: 'Y-m-d\TH:i:s.uP'
            trustedDateSerializers: [App\Models\ApplicationModel::serializeDate]
            dateCastExceptions:
                - {class: App\Models\Event, properties: [calendar_date]}
```

Trust applies to the named declaring class and method, including models that
inherit that implementation. Separate classes consuming the same trait each
need verification or their own trust declaration, because class constants can
change the trait's behavior. An overriding implementation needs its own
verification. Exceptions represent intentionally different date
semantics or a separately verified custom serialization contract; they do not
prove runtime behavior. Dynamic setters or changes outside a constructor,
custom accessors/casts returning serialized arrays, resource serializers and
query-builder writes require application tests. PostgreSQL column precision
and time zones use the separate [PostgreSQL instant rule](laravel-postgres-instant.md).

## PHPDoc and dynamic configuration

The bridge obtains column names before reading annotations, so an incorrect
`@property` cannot hide the schema column. Accessor getter types take precedence
over cast/schema types. Statically recognized setter-only mutators retain the
database/cast readable type. Legacy getters and setters retain Laravel's precedence.
Indirect factories or callbacks whose presence is unknown leave readable-type
and readonly consistency to application review. Read and write contracts are distinct: a getter over a
database field alone does not make the field read-only. Native PHPStan return
type checks remain responsible for validating accessor/relationship method
bodies against their declared generic types.

```neon
parameters:
    noldors:
        eloquent:
            dynamicConfiguration:
                - {class: App\Models\TenantRecord, properties: [connection, table]}
                - {class: App\Models\PackageRecord, properties: [casts]}
            writableProperties:
                - {class: App\Models\ImportedRecord, properties: [id]}
            queryProperties:
                - {class: App\Models\ReportRow, properties: [rank, total_count]}
```

Known direct constructor changes, including explicit parent constructor
delegation, preserve dynamic declarations. Writable properties still need
`@property` and a review of their actual setter/write types; the consistency
check validates the readable side. Table/connection
exceptions and detected constructor changes make schema-based checks unknown;
the bridge does not guess from an unconstructed model's fallback table.
Other constructor helpers, runtime configuration and package initialization need
explicit exceptions and manual verification. Exceptions inherit to subclasses.

Type consistency is experimental and bounded by independent Larastan metadata.
Larastan 3.13.2 incorrectly marks `timestampTz`, `timeTz` and `year` columns nullable.
A small detector over Larastan's migration files disables **only nullability
comparison** for matching column names, conservatively across tables. It does
not infer or replace schema types. Dynamic column declarations, generic
`addColumn()` and unparseable migrations make all migration nullability
comparisons uncertain. Accessor and relationship nullability remains checked.
Refined shapes are checked against available getter/cast types; a broad JSON
cast cannot establish the keys or application invariants of a shape. Query-only
property types require an application contract. Missing PHPDoc entries are not
generated or required by this check.

## Morph aliases

```php
Relation::enforceMorphMap(['event' => Event::class]);

// Application/factory code:
$attributes = ['subject_type' => Relation::getMorphAlias(Event::class)];
```

Configure discriminator column names explicitly. The check recognizes literal
array values, property assignments and `where`/`orWhere` values for these names,
including qualified column names. It does not reject ordinary display strings
that happen to equal an alias. Anonymous/named Laravel migration classes are
exempt. Arbitrary SQL, discriminator variables, nested query operators and
dynamic registrations require manual verification. The map check establishes
declared registration in analyzed source, not provider execution, boot order,
conditional coverage or the runtime effect of replacing an existing map.

Native lists such as `Relation::morphMap([Event::class])` also establish declared
model registration. Alias-literal checking derives table names from static
properties, nearest class/trait `Table` attributes and Laravel's default naming.
Dynamic table construction and custom `getTable()` implementations leave those
derived alias values unknown and require manual verification.

## Pivots

```php
return $this->belongsToMany(User::class)
    ->using(Membership::class)
    ->as('membership')
    ->chaperone(declaring: 'team', related: 'user');
```

Direct/fluent returns, custom pivot classes used as the table argument and
straight-line local relationship variables are supported. Both morph directions
are checked against their actual declaring/related types. `chaperone()` before
`using()` is a no-op; `withoutChaperone()` clears both inverses. Existing `as()`
accessors are preserved. Common query/pivot modifiers are transparent; custom
helpers, macros and ambiguous control-flow mutations report unverifiable state.
Declare precise inverse return generics so target models can be verified.

Static checks do not establish inverse object identity, reuse across shared
eager-loaded records or SQL query counts. Verify those with application fixtures,
along with lifecycle, authorization, audit and business requirements.
