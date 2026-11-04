# Events, listeners and observers

`LaravelEventContractRule` checks selected `events`, `listeners` and `observers`. See [role scopes and exclusions](../laravel-application.md). These are distinct Laravel contracts:

| Role | Required declaration | Identifier |
| --- | --- | --- |
| Concrete event | Native `final readonly` | `laravel.eventDeclaration` |
| Abstract event base | `abstract readonly` | `laravel.eventDeclaration` |
| Concrete listener | Native `final` | `laravel.listenerFinal` |
| Synchronous separate listener | `ShouldHandleEventsAfterCommit` | `laravel.listenerAfterCommit` |
| Queued listener | [ShouldQueueAfterCommit](laravel-queue-after-commit.md) | `laravel.queueAfterCommit` |
| Model observer | Immediate handling, with no deferred/queued handler contract | `laravel.observerAfterCommit` |

Events may be dispatched synchronously inside a business transaction, including events named `Created`, `Updated` or `Deleted` and audit events. The rule does not require `ShouldDispatchAfterCommit` or emit `laravel.eventAfterCommit`. A deliberately implemented `ShouldDispatchAfterCommit` remains allowed when the whole event should wait for commit.

Individual completed-fact listeners select after-commit handling through `ShouldHandleEventsAfterCommit` or the applicable queued-listener contract. Deferring the whole event delays all its listeners, including a synchronous audit listener that must write a transactional outbox atomically with the business change. Such immediate infrastructure listeners must remain outside the configured separate completed-fact listener scope; the listener checks are unchanged.

```php
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final readonly class OrderCreated
{
    public function __construct(public int $orderId) {}
}

final class NotifyIntegration implements ShouldHandleEventsAfterCommit
{
    public function handle(OrderCreated $event): void {}
}
```

The event check is for native modifiers, not PHPDoc immutability, and does not establish deep immutability of referenced objects. Laravel's generated `InteractsWithSockets` and `InteractsWithBroadcasting` traits carry mutable state incompatible with a readonly class. Remove unused mutable traits; configure a narrow exclusion when a required external mutable contract cannot be changed. Do not apply an automatic readonly rewrite.

## Legacy suffix configuration

`noldors.laravel.completedEventSuffixes` is deprecated and has no effect. Existing values and `completedEventSuffixes!:` overrides remain accepted as lists of strings for configuration compatibility; they can be removed. The option can be removed from the package in a future breaking release. All selected events receive the same declaration checks regardless of their name or interfaces. Inherited listener interfaces are accepted.

## Immediate observers

Observer scope takes precedence over listener and job scopes. Observers must not implement `ShouldHandleEventsAfterCommit` or `ShouldQueue` (including their inherited contracts), enable a known truthy public `$afterCommit` flag, or call the native `Queueable::afterCommit()` mechanism. The class and direct-call/property rules check these known deferral paths. Constructor assignments are included; unknown indirect runtime changes are not proven safe.

Observers remain responsible for immediate model lifecycle work. Separate listeners react to completed facts. Laravel 13.39 leaves native before-event handlers named `creating`, `updating`, `saving`, `deleting`, `restoring` and `forceDeleting` immediate even when the deferred-listener marker is present; the project policy still rejects that marker on observers. Class declarations alone do not prove how an application registered or dispatched a handler.

The shared formatter already removes redundant property `readonly` inside readonly classes; PHPStan does not duplicate that formatting check.
