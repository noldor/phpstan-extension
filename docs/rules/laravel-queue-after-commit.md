# Queued after-commit contracts

`LaravelQueueContractRule`, `LaravelQueueOverrideRule` and its callable adapter are included in the Laravel set. Configure application `jobs`, `listeners`, `notifications` and `observers` through the [role scopes](../laravel-application.md).

Every concrete selected dedicated job must implement `Illuminate\Contracts\Queue\ShouldQueueAfterCommit`, even if it forgot both queue interfaces. Selected listeners and notifications require that interface only when they already implement `ShouldQueue`. Inherited interfaces satisfy the contract. The diagnostic identifier is `laravel.queueAfterCommit`.

```php
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

final class RebuildIndex implements ShouldQueueAfterCommit
{
    public function __construct(public int $recordId) {}

    public function handle(): void {}
}
```

Synchronous listeners stay synchronous and follow their [own after-commit contract](laravel-event-contracts.md). Immediate notifications retain their own delivery contract. Abstract job bases are not concrete producers.

## Known job bypasses

The rules report `laravel.queueBeforeCommit` for known public `$afterCommit = false` defaults, inherited defaults, promoted defaults and direct false assignments on selected jobs. They also reject native `Queueable::beforeCommit()` calls and first-class callable references. In current Laravel an explicitly false job flag overrides `ShouldQueueAfterCommit`.

Supported direct pending-dispatch expressions include:

```php
RebuildIndex::dispatch()->beforeCommit();
RebuildIndex::dispatch()->onQueue('search')->beforeCommit();
dispatch(new RebuildIndex(1))->beforeCommit();
```

These examples assume the job uses the framework's `Dispatchable` and `Queueable` traits. The checker resolves native method provenance and job identity; it does not reject unrelated methods solely because they share a name. Private/protected flags that the framework cannot read are not treated as public dispatch overrides.

`PendingDispatch` does not expose a generic job type. The checker follows supported direct AST chains from native `Dispatchable::dispatch()` / `dispatchIf()` / `dispatchUnless()` methods with the native `newPendingDispatch()` factory, or the global `dispatch($job)` helper. Unknown factories, wrappers and split aliases such as `$pending = Job::dispatch(); $pending->beforeCommit()` are outside that provenance analysis. This is not a whole-program guarantee that no dispatch can happen early.

Queued listeners and notifications propagate their after-commit marker to an internal framework job; their producer properties have different precedence from a dedicated job. Do not apply the job override policy indiscriminately to those objects.

Larastan's optional `checkDispatchInTransactionAfterCommit` checks supported dispatch sites inside recognized transaction closures. It is complementary and does not replace this declaration policy. Queue names, attribute preferences and worker provisioning are separate contracts.
