# Use-case declarations

`LaravelUseCaseRule` is included in the Laravel set. Select `noldors.laravel.useCases` under the [application scope configuration](../laravel-application.md).

Concrete selected classes must:

- Use the native `final` modifier (`architecture.useCaseFinal`). PHPDoc `@final` is insufficient.
- Expose a public native `handle()` method, including an inherited one (`architecture.useCaseEntryPoint`).
- Have an operation name without a `UseCase` suffix (`architecture.useCaseName`).

Abstract bases are not required to be final. Interfaces and traits are not concrete use cases. Preserve an explicitly required extension contract through the configured scope exclusions.

```php
final class CreateOrder
{
    public function handle(): void
    {
        // Complete the application operation.
    }
}
```

The check does not establish domain boundaries, whether an operation needs a use case, or whether its result is fully persisted. [Validation error ownership](laravel-use-case-validation.md) is checked separately.
