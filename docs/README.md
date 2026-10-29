# Rule documentation

## Rule sets

| Set | Configuration | Contents |
| --- | --- | --- |
| PHP | `src/php.neon` | Reference parameters, exception translation, JSON error flags, configured collection ownership and DTO infrastructure restrictions. |
| Laravel | `src/laravel.neon` | Includes PHP, adds Laravel DTO infrastructure selectors, enables validation rules and provides a configurable PostgreSQL instant check. |
| Eloquent property bridge | `src/laravel-model-properties.neon` | Includes Laravel and adds computed-accessor and schema-aware PHPDoc checks; requires a separately included Larastan extension. |

Include one set in your PHPStan configuration. The Laravel set already includes
PHP; including both files separately is unnecessary. The package does not enable
rules automatically through extension-installer. Keep your existing Larastan
configuration when analysing Laravel APIs.

`src/rules.neon` remains an alias for the Laravel set for consumers of version
0.0.1. It receives the same new PHP checks as the Laravel entry point.

## PHP checks

| Check | Identifier | Documentation |
| --- | --- | --- |
| `NoReferenceParameterRule` | `api.referenceParameter` | [Reference parameters](rules/no-reference-parameter.md) |
| `PreservePreviousExceptionRule` | `exceptions.missingPrevious` | [Exception translation](rules/preserve-previous-exception.md) |
| `JsonThrowOnErrorRule` | `json.missingThrowOnError` | [JSON error flags](rules/json-throw-on-error.md) |
| `MutableCollectionExposureRule` | `encapsulation.mutableCollectionExposure` | [Mutable collections](rules/mutable-collection-exposure.md) |
| `DtoInfrastructureUsageExtension` | `architecture.dtoInfrastructureUsage` | [DTO infrastructure usage](rules/dto-infrastructure-usage.md) |

Reference-parameter, exception-translation and JSON-flag checks run when a set is connected.
Collection owners and DTO scopes are empty by default; configure the classes,
interfaces or namespaces that actually have those contracts in the consuming
project. The package does not guess class roles from suffixes or application
directory names.

The PHP set's [infrastructure selectors](../src/php.neon) cover PDO, selected PSR
client/cache/logging APIs and common filesystem, process, mail and cURL functions.
The [Laravel additions](../src/laravel.neon) cover the selected database, HTTP,
queue, filesystem and cache APIs. These are explicit direct-call restrictions,
not a complete catalogue of every operation with side effects. Extend or replace
the selectors for the project's installed APIs as described in the rule docs.

## Laravel rules

| Rule | Documentation |
| --- | --- |
| `LaravelValidationLocationRule` | [Validation declarations](rules/laravel-validation-location.md) |
| `LaravelValidationCallableRule` | [Validation callables](rules/laravel-validation-callable.md) |
| `LaravelPostgresInstantRule` | [PostgreSQL instants](rules/laravel-postgres-instant.md) |
| `LaravelUseCaseRule` | [Use-case declarations](rules/laravel-use-case.md) |
| `LaravelUseCaseExceptionRule` and validation usage extension | [Use-case validation boundary](rules/laravel-use-case-validation.md) |
| `LaravelQueueContractRule`, override rule and callable adapter | [Queued after-commit contracts](rules/laravel-queue-after-commit.md) |
| `LaravelEventContractRule` | [Events, listeners and observers](rules/laravel-event-contracts.md) |
| `LaravelJobPayloadRule` and assignment rule | [Job payloads](rules/laravel-job-payload.md) |
| `LaravelBulkLifecycleRule` | [Bulk lifecycle operations](rules/laravel-bulk-lifecycle.md) |
| `LaravelValidationSideEffectsExtension` | [Validation side effects](rules/laravel-validation-side-effects.md) |
| Eloquent model rules and collectors | [Models, observers, dates, PHPDoc, morph aliases and pivots](rules/eloquent-models.md) |

The Eloquent model scope is empty by default. Its [attribute catalog](laravel-attributes.md)
lists all 25 Laravel 13.38 Eloquent attributes and their recommended uses.

Application-contract scopes are empty by default; see [scope configuration and compatibility](laravel-application.md).

The two validation-location rules report `architecture.validationLocation`. They analyse
every file selected by PHPStan's `paths` and exclusions, including files outside `app/`.
Validator namespaces are unrestricted; placement is checked using file paths.

The PostgreSQL instant rule reports `laravel.postgresqlInstant` for configured
application-owned PostgreSQL paths. Its scope is empty by default; configure it
as shown in its [documentation](rules/laravel-postgres-instant.md).

## Allowed validation directories

The default allowed directories, including their nested directories, are:

- `app/GraphQL/Validators`
- `app/Http/Requests`
- `app/Validation`
- `app/Validators`
- `app/Providers`
- `app/Rules`
- `tests`

Global-scope declarations remain forbidden.

To replace the defaults:

```neon
parameters:
    noldors:
        allowedValidationPaths!:
            - src/Domain/Validation
            - modules/Billing/Requests
```

Validation declarations are forbidden elsewhere in the analysed files. Paths
can be absolute or relative to the directory from which PHPStan is run. They
identify directories, not namespaces or glob patterns; similarly named
neighbouring directories do not match.

The `!` suffix replaces the default list; omit it to append directories. Use
`allowedValidationPaths!: []` to forbid declarations throughout the analysis
scope.
