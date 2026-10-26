# Noldors PHPStan extension

Custom PHPStan rule sets for PHP and Laravel projects.
Requires PHP >=8.5, `ext-mbstring` and PHPStan ^2.3.

## Installation

```sh
composer require --dev noldors/phpstan-extension
```

## Connection

For a PHP project, add to `phpstan.neon`:

```neon
includes:
    - vendor/noldors/phpstan-extension/src/php.neon

parameters:
    paths:
        - src
```

For a Laravel project, use the Laravel set, which also includes the PHP set:

```neon
includes:
    - vendor/noldors/phpstan-extension/src/laravel.neon

parameters:
    paths:
        - app
        - src
```

Keep only existing project directories in `paths`. The PHP set checks reference
parameters, exception translation and mandatory JSON error flags. Its collection
and DTO checks require explicit owner/DTO scopes; see the
[rule documentation](docs/README.md).
The Laravel set also enables validation placement checks and adds infrastructure
APIs to the DTO configuration. Keep your existing Larastan configuration when
analysing Laravel APIs.

The sets are opt-in. Existing `src/rules.neon` includes remain supported and
load the Laravel set.

## Documentation

See the [rule documentation](docs/README.md) for the rule catalogue, examples and
configuration options.

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

Composer and Git dist archives contain only `composer.json`, MIT `license.txt`
and `src/`, including all rule-set configuration files. Documentation, tests,
tooling and dependencies stay in the source checkout. `archive.exclude` and
`.gitattributes` enforce this allowlist. A source checkout (`--prefer-source`)
contains the full repository.

See [license.txt](license.txt).

## Releases

Work happens on branches from `master`, through PRs with passing quality checks
and tests. After an ordinary merge, GitHub Actions checks `master` again, creates
a SemVer tag and verifies its publication through the Packagist webhook. See the
[release workflow and activation guide](.github/RELEASING.md).
