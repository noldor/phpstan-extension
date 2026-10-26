# Rule documentation

## Rule sets

| Set | Configuration | Contents |
| --- | --- | --- |
| PHP | `src/php.neon` | Reference parameters, exception translation, JSON error flags, configured collection ownership and DTO infrastructure restrictions. |
| Laravel | `src/laravel.neon` | Includes PHP, adds Laravel DTO infrastructure selectors and enables both validation rules below. |

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

Both rules report `architecture.validationLocation`. They analyse every file
selected by PHPStan's `paths` and exclusions, including files outside `app/`.
Validator namespaces are unrestricted; placement is checked using file paths.

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
