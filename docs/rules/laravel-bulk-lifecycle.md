# Soft-delete bulk lifecycle operations

`LaravelBulkLifecycleRule` reports `laravel.bulkLifecycle` for `delete()`, `restore()` and `forceDelete()` on builders/relations whose resolved target is a selected soft-delete model. Individual model lifecycle methods remain valid.

Configure the [model scope](../laravel-application.md), and add a custom lifecycle trait only when it represents the consumer's actual soft-delete implementation:

```neon
parameters:
    noldors:
        laravel:
            softDeleteModels:
                namespaces: [App\Models]
            softDeleteTraits:
                - App\Models\Concerns\CustomSoftDeletes
```

The default trait is `Illuminate\Database\Eloquent\SoftDeletes`. A selected physical-deletion model without a configured lifecycle trait is not covered. The checker does not impose visibility defaults or require `withoutTrashed()` on ordinary queries.

```php
// Rejected outside an approved bulk operation.
Order::query()->whereKey($ids)->delete();

// Accepted instance lifecycle operation.
$order->delete();
$order->restore();
```

## Explicit bulk exceptions

A concrete operation may have an approved bulk contract with understood lifecycle, observer, audit and downstream effects. Document that contract and prove it through real regression tests before adding its calling class to:

```neon
parameters:
    noldors:
        laravel:
            bulkLifecycleOperations:
                classes: [App\Maintenance\ApprovedArchiveOperation]
```

The allowlist cannot establish that those guarantees are preserved. Use narrow operation classes rather than exempting all application services.

The check uses Eloquent builder/relation model types supplied by Larastan or equivalent metadata. Plain query builders, raw SQL, dynamic macros and opaque wrappers lack a reliable model association and are not a complete lifecycle audit. Direct lifecycle-field writes are a separate contract.

`Model::destroy()` and standard `SoftDeletes::forceDestroy()` iterate individual models and dispatch their events; they are not falsely reported as eventless builder operations. Framework `restoreOrCreate()` / `createOrRestore()` likewise have different instance lifecycle behavior.
