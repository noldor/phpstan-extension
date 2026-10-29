# Events, listeners and observers

`LaravelEventContractRule` checks selected `events`, `listeners` and `observers`. See [role scopes and exclusions](../laravel-application.md). These are distinct Laravel contracts:

| Role | Required declaration | Identifier |
| --- | --- | --- |
| Concrete event | Native `final readonly` | `laravel.eventDeclaration` |
| Abstract event base | `abstract readonly` | `laravel.eventDeclaration` |
| Completed lifecycle event | `ShouldDispatchAfterCommit` | `laravel.eventAfterCommit` |
| Concrete listener | Native `final` | `laravel.listenerFinal` |
| Synchronous separate listener | `ShouldHandleEventsAfterCommit` | `laravel.listenerAfterCommit` |
| Queued listener | [ShouldQueueAfterCommit](laravel-queue-after-commit.md) | `laravel.queueAfterCommit` |
| Model observer | Immediate handling, with no deferred/queued handler contract | `laravel.observerAfterCommit` |

`ShouldHandleEventsAfterCommit` belongs on a listener, not on its event. `ShouldDispatchAfterCommit` belongs on the event when publishing its completed fact must wait for commit.

```php
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final readonly class OrderCreated implements ShouldDispatchAfterCommit
{
    public function __construct(public int $orderId) {}
}

final class NotifyIntegration implements ShouldHandleEventsAfterCommit
{
    public function handle(OrderCreated $event): void {}
}
```

The event check is for native modifiers, not PHPDoc immutability, and does not establish deep immutability of referenced objects. Laravel's generated `InteractsWithSockets` and `InteractsWithBroadcasting` traits carry mutable state incompatible with a readonly class. Remove unused mutable traits; configure a narrow exclusion when a required external mutable contract cannot be changed. Do not apply an automatic readonly rewrite.

## Completed facts

Within the configured event scope, these name suffixes select the completed lifecycle convention:

```neon
parameters:
    noldors:
        laravel:
            completedEventSuffixes: [Created, Updated, Deleted, Restored, Saved, ForceDeleted, Trashed]
```

Matching is case-insensitive. Names express an explicitly adopted convention, not a proof of business semantics. Replace the list with `completedEventSuffixes!:` for another naming contract. Other selected events still receive declaration checks. Inherited event/listener interfaces are accepted.

## Immediate observers

Observer scope takes precedence over listener and job scopes. Observers must not implement `ShouldHandleEventsAfterCommit` or `ShouldQueue` (including their inherited contracts), enable a known truthy public `$afterCommit` flag, or call the native `Queueable::afterCommit()` mechanism. The class and direct-call/property rules check these known deferral paths. Constructor assignments are included; unknown indirect runtime changes are not proven safe.

Observers remain responsible for immediate model lifecycle work. Separate listeners react to completed facts. Laravel 13.39 leaves native before-event handlers named `creating`, `updating`, `saving`, `deleting`, `restoring` and `forceDeleting` immediate even when the deferred-listener marker is present; the project policy still rejects that marker on observers. Class declarations alone do not prove how an application registered or dispatched a handler.

The shared formatter already removes redundant property `readonly` inside readonly classes; PHPStan does not duplicate that formatting check.
