# Laravel application contracts

These opt-in checks enforce explicitly selected application contracts. Include the Laravel set and keep the consumer's compatible Larastan configuration. The PHP set exposes the configuration schema but does not register Laravel rules. The legacy `src/rules.neon` entry point includes the same Laravel rules.

## Select application roles

Every scope is empty by default. Select owned classes, base classes/interfaces or namespaces; the package does not infer roles from `App`, application directory names or suffixes alone. PHPStan must analyze the selected source paths.

```neon
parameters:
    noldors:
        laravel:
            useCases:
                namespaces: [App\UseCases]
            events:
                namespaces: [App\Events]
            jobs:
                namespaces: [App\Jobs]
            listeners:
                namespaces: [App\Listeners]
            notifications:
                namespaces: [App\Notifications]
            observers:
                namespaces: [App\Observers]
            validators:
                classes:
                    - Illuminate\Foundation\Http\FormRequest
                    - Illuminate\Contracts\Validation\ValidationRule
                namespaces: [App\Validation]
            softDeleteModels:
                namespaces: [App\Models]
```

Replace these example namespaces with the consumer's actual roles. Selecting a base class or interface includes descendants/implementations. Namespace matching respects segment boundaries: `App\JobsExtra` is outside `App\Jobs`.

Every role supports four list parameters: `classes`, `namespaces`, `excludedClasses`, and `excludedNamespaces`. Inclusion lists are alternatives; exclusions take precedence and include descendants/subnamespaces. For an explicit extension contract:

```neon
parameters:
    noldors:
        laravel:
            useCases:
                excludedClasses: [App\UseCases\RequiredExtensionPoint]
```

NEON merges inherited lists. Use the `!` suffix to replace a list, for example `namespaces!: []`, rather than appending to it. Configuration types and unknown keys are validated before analysis.

Observer scope takes precedence when it overlaps listener or job scope. An observer is not made compliant by adding a listener's deferred contract. Select observers explicitly; a suffix or a method called `created()` is insufficient evidence of their role.

## Checks

- [Use-case declarations](rules/laravel-use-case.md).
- [Use-case validation boundary](rules/laravel-use-case-validation.md).
- [Queued after-commit contracts and bypasses](rules/laravel-queue-after-commit.md).
- [Events, listeners and immediate observers](rules/laravel-event-contracts.md).
- [Dedicated-job model payloads](rules/laravel-job-payload.md).
- [Soft-delete bulk lifecycle operations](rules/laravel-bulk-lifecycle.md).
- [Validation side effects](rules/laravel-validation-side-effects.md).

## Compatibility and guarantees

The package requires PHP >=8.5 and PHPStan ^2.3. Contract checks require the relevant interfaces in the consumer's installed Laravel version: `ShouldQueueAfterCommit`, `ShouldDispatchAfterCommit` and `ShouldHandleEventsAfterCommit`. Native readonly declarations are checked when PHPStan's configured PHP target supports readonly classes. The consumer fixtures exercise real Laravel 13 and Larastan 3 metadata; development dependencies are not installed in consumers.

Forwarded Eloquent methods, relation model types and facade implementations require compatible Larastan or equivalent reflection metadata. Unknown receiver/model/payload types are not reported as proven violations. Keep strict native typing checks enabled to address missing types separately.

The checks establish finite declarations, known retained payload types and direct API restrictions. They do not prove registration, commit/rollback behavior, worker delivery, snapshot completeness, idempotency, authorization, bulk-exception correctness or purity through arbitrary helper calls. Keep the real entry-point and delivery-boundary regression tests required by the application.
