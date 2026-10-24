# Rule documentation

## Rule sets

| Set | Configuration | Contents |
| --- | --- | --- |
| PHP | `src/php.neon` | Framework-independent rules. Currently empty. |
| Laravel | `src/laravel.neon` | Includes the PHP set and both Laravel validation rules below. |

Include one set in your PHPStan configuration. The Laravel set already includes
PHP; including both files separately is unnecessary. The package does not enable
rules automatically through extension-installer. Keep your existing Larastan
configuration when analysing Laravel APIs.

`src/rules.neon` remains an alias for the Laravel set for consumers of version
0.0.1. Existing configuration keeps the same behaviour.

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
