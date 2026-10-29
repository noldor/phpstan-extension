# Queue routes and native queue attributes

The Laravel set checks the configured application `jobs`, queued `listeners`
and queued `notifications` from the [role scopes](../laravel-application.md).
Observers and unselected classes are excluded. Empty scopes leave existing
projects unchanged. These checks complement the
[after-commit contracts](laravel-queue-after-commit.md).

| Identifier | Contract |
| --- | --- |
| `laravel.queueRoute` | A concrete job or queued listener declares its own queue. |
| `laravel.notificationQueues` | A queued notification declares channel routes through public `viaQueues()`. |
| `laravel.externalQueueRoute` | A selected producer's queue or connection cannot be assigned, registered or forwarded from outside its class. |
| `laravel.queueAttribute` | Static queue, connection, tries, timeout and backoff declarations use available native queue attributes. |

## Jobs and listeners

Static routing belongs on the producer itself:

```php
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Connection;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Timeout;
use Illuminate\Queue\Attributes\Tries;

#[Queue('search')]
#[Connection('redis')]
#[Tries(3)]
#[Timeout(60)]
#[Backoff(5, 30)]
final class RebuildSearch implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(public int $recordId) {}
}
```

Inherited and directly applied trait attributes follow Laravel's attribute
lookup. Public non-static queue properties remain recognized as declarations,
but static defaults receive the attribute diagnostic when that attribute exists
in the installed framework. Private and protected properties cannot provide a
framework-readable route.

Dynamic jobs select their queue in their constructor, through a public promoted
queue property, a direct `$this->queue` assignment or native `onQueue()`, including
native fluent chains on `$this`. The constructor analysis follows sequential
statements and `if`/`elseif`/`else` branches, including early returns and subsequent
queue resets. It does not infer routes assigned by arbitrary delegated methods,
loops, `switch`, `try` blocks or custom setter implementations.

Queued listeners use a public `viaQueue()` when their route depends on event
data. A listener's `viaQueue()` takes precedence over queue attributes and
properties; a constant null result does not satisfy the internal-route contract.
Laravel jobs do not consume a `viaQueue()` method. Synchronous listeners retain
their synchronous delivery contract.

```php
final class DeliverTenantEvent implements ShouldQueueAfterCommit
{
    public function viaQueue(TenantEvent $event): string
    {
        return 'tenant-' . $event->tenantId;
    }
}
```

## Notifications

Every selected queued notification supplies an effective public, non-static
`viaQueues()` method, including inherited implementations. A statically empty
map, a null queue or a missing statically known `via()` channel requires an
internal fallback queue. An explicit empty queue is always rejected. Dynamic maps and dynamic
channel selection are accepted without guessing runtime values.

```php
public function viaQueues(): array
{
    return ['mail' => 'mail', 'database' => 'notifications'];
}
```

The notification's own queue attribute/property or constructor route can provide
the fallback. `viaQueues()` remains required. Static channel maps stay in this
method: a single queue attribute cannot replace per-channel routing.

## External overrides

Native `onQueue()`, `onConnection()`, `allOnQueue()` and `allOnConnection()` calls
on selected producer instances are rejected outside that same `$this` object.
First-class method callable references are checked too. Known queue/connection
property writes include constant dynamic property names, compound assignments,
references and string-offset writes. An unrelated same-named method is accepted.

The checker follows native direct pending-dispatch expressions from
`Job::dispatch()`/`dispatchIf()`/`dispatchUnless()` and global `dispatch($job)`,
as well as native fluent pending calls. It also recognizes direct native
`Bus::chain()`/`Bus::batch()`, dispatcher factories and `PendingChain` /
`PendingBatch` construction when a selected producer appears in the known jobs.
External explicit queue arguments in supported `pushOn()`, `laterOn()`, `push()`,
`later()` and `bulk()` calls are rejected for selected jobs.
Selecting a connection through a direct `Queue::connection('name')->push(...)`
or equivalent manager expression is also an external override.

Collectors correlate `Queue::route()`, `QueueManager::route()` and
`QueueRoutes::set()` registrations with analysed selected producers, including
registrations on their parents, interfaces and traits. Known `Queue::forward()`,
manager and route-manager forwards are rejected when their source queue matches
a selected producer's statically declared queue or notification channel map.
These checks require the producer declarations and route registrations to be
inside the analysis scope.

Pending objects do not carry the original job type in Laravel. Split aliases,
arbitrary wrappers/factories, dynamic class registrations and dynamically
computed forwarded queue names can therefore remain unverifiable. This is
bounded static analysis, not a whole-program routing guarantee.

## Static execution settings

When the installed framework provides the corresponding native attribute,
public static-value declarations of `queue`, `connection`, `tries`, `timeout`
and `backoff` receive `laravel.queueAttribute`. Constant public `tries()` and
`backoff()` methods, and listener `viaQueue()` / `viaConnection()` methods, are
also diagnosed. Direct constant assignments to these settings are checked.
Dynamic methods and constructor calculations remain methods and calculations;
no attribute is required merely because a setting is absent.

Attributes do not initialize public properties. If job logic needs the same
value, share a class constant between the attribute and that logic. These rules
provide diagnostics and never rewrite properties, method bodies or reads.

The implementation is verified against Laravel 13.38. Attribute preferences are
disabled individually when the corresponding attribute class is unavailable.
Queue names and connections are consumer contracts. Worker provisioning,
delivery, retry safety and runtime routing values require application tests.
