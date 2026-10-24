# Noldors PHPStan extension

Custom PHPStan rules. Requires PHP >=8.5, `ext-mbstring` and PHPStan ^2.3.

## Rules

| Rule | Description |
| --- | --- |
| `LaravelValidationLocationRule` | Allows Laravel validation declarations only inside classes in configured directories. Checks helpers, factories, request/controller validation, rule construction and mutations, callbacks, and `rules()` methods on FormRequest and Lighthouse validators. Executing prepared validators remains allowed. |
| `LaravelValidationCallableRule` | Applies the same restriction to first-class function, method, static method and constructor callables. |

Both rules report `architecture.validationLocation`. Your own validator
namespaces are unrestricted; the location check uses file paths.

## Installation and connection

After publication on Packagist:

```sh
composer require --dev noldors/phpstan-extension
```

Add to your project's `phpstan.neon`:

```neon
includes:
    - vendor/noldors/phpstan-extension/src/rules.neon

parameters:
    paths:
        - app
        - src
```

This enables both rules, including all four callable node types. The rules check
every file selected by PHPStan's `paths` and exclusions, including files outside
`app/`. Keep your existing Larastan configuration when analysing Laravel APIs.
The rules are opt-in and are not automatically enabled by extension-installer.

## Allowed validation directories

The defaults are `app/GraphQL/Validators`, `app/Http/Requests` and `app/Validation`,
including their nested directories. Global-scope declarations remain forbidden.

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

## Development

```sh
composer install
composer format
composer check
```

`check` runs Pint, PHPStan at maximum level, Phanalist and PHPUnit. Tests run in
random order. Intentionally invalid fixtures are excluded from direct static
analysis and formatting and are analysed by the rule tests instead.

Laravel is a development-only dependency. Lighthouse is represented by a minimal
test-only validator stub loaded via `autoload-dev`; the tests check inheritance
detection, not Lighthouse's runtime behaviour. Neither framework is installed
when this package is used as a dependency.

## Distribution

Composer archives and Git distribution archives contain only:

```text
composer.json
license.txt
src/
```

`src/rules.neon` is included with the rule classes. README, tests, tooling
configuration and dependencies stay in the development checkout. `archive.exclude`
and `.gitattributes` enforce this allowlist, including for newly added root files.
A source checkout (`--prefer-source`) contains the full Git repository.

The package is licensed under MIT; see [license.txt](license.txt).
No release has been published yet.

## Releases

Work happens on branches from `master`, through PRs with passing quality checks
and tests. After an ordinary merge, GitHub Actions checks `master` again, creates a
SemVer tag (starting with `v0.0.1`) and updates Packagist. See the
[release workflow and activation guide](.github/RELEASING.md).
