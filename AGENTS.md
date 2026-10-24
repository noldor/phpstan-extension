# PHPStan extension development

Before adding or changing a check, read PHPStan's official
[Extension Types](https://phpstan.org/developing-extensions/extension-types)
overview and the documentation for the relevant extension points. Choose the
mechanism that matches the requested behaviour and is supported by the package's
PHPStan version; explain the choice briefly before implementation.

Custom `Rule` implementations are one option. Consider restricted usage
extensions for access restrictions, collectors for cross-file analysis,
type-inference extensions for inferred types and reflection extensions for
class metadata. Use the appropriate documented mechanism rather than defaulting
every new check to a custom rule. Verify its registration and behaviour through
consumer-facing tests.

Register framework-independent extensions in `src/php.neon` and Laravel-specific
extensions in `src/laravel.neon`. The Laravel set includes the PHP set.
Keep `src/rules.neon` compatible with its published Laravel entry point.
Keep installation and set connection examples in README.md; document individual
checks and configuration under `docs/`.
