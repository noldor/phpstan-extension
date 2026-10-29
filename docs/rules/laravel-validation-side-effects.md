# Validation side effects

`LaravelValidationSideEffectsExtension` reports `architecture.validationSideEffect` for configured direct write/delivery APIs inside selected validator/rule classes. Connect the Laravel set and configure `noldors.laravel.validators` using the [application scopes](../laravel-application.md).

Default method selectors cover known Eloquent/query-builder writes and lifecycle methods (including `destroy()`/`forceDestroy()`), relation/pivot writes, database write statements, bus/event/queue/notification/mail delivery, HTTP requests, filesystem writes and cache mutations. Function selectors include dispatch/event helpers and selected file/process/mail/cURL effects. Read queries, `$fail()`, authentication reads and preparation of pending HTTP or mail requests remain valid.

```php
// Rejected inside a selected validation class.
$order->save();
DispatchJob::dispatch();

// Allowed reads and failure reporting.
$exists = $query->exists();
$fail('validation.order_unavailable')->translate();
```

The [default selectors](../../src/php.neon) are explicit, not a complete effect catalogue. Add the consumer's effectful APIs:

```neon
parameters:
    noldors:
        laravel:
            validationSideEffects:
                forbiddenMethods:
                    - {class: App\Infrastructure\AuditWriter, methods: [record]}
                forbiddenFunctions:
                    - App\Infrastructure\deliver_message
```

Method selectors use a class/interface/trait and method names; `'*'` selects every method. They match the declaring API, descendants/implementations and selected methods on trait-bearing classes. Function selectors use exact resolved fully qualified names. Matching is case-insensitive and permits a leading namespace separator.

Use `forbiddenMethods!:` / `forbiddenFunctions!:` to replace the respective defaults. A broad `'*'` restriction can prohibit reads; select write/effect methods individually when the API also has legitimate validation reads. These selectors are separate from DTO infrastructure restrictions because validators may query the database.

PHPStan's restricted-usage mechanism covers ordinary resolved instance/static calls and first-class references. Larastan facade reflection can expose underlying concrete methods, so selectors include contracts/concrete APIs rather than only facade names. Arbitrary helpers, unresolved callables, macros, raw statement semantics and transitive effects remain outside the finite denylist. This check supports direct-effect prevention, not a proof that a whole validation call graph is pure.
