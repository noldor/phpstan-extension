<?php

declare(strict_types = 1);

namespace QueueRoutingFixtures\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Queue\Attributes\Queue;

#[Queue('trait-queue')]
trait RouteAttribute {}

final class TraitRoute implements ShouldQueueAfterCommit
{
    use RouteAttribute;
}

final class PrivateQueue implements ShouldQueueAfterCommit // @expect laravel.queueRoute
{
    private string $queue = 'inaccessible';
}

final class DynamicFluent implements ShouldQueueAfterCommit
{
    use Queueable;
    public function __construct(string $queue, string $connection)
    {
        $this->onConnection($connection)->onQueue($queue);
    }
}

final class EarlyReturn implements ShouldQueueAfterCommit // @expect laravel.queueRoute
{
    use Queueable;
    public function __construct(bool $skip, string $queue)
    {
        if ($skip) {
            return;
        }
        $this->onQueue($queue);
    }
}

#[Queue('other')]
final class NativeOverride implements ShouldQueueAfterCommit
{
    use Queueable;
    public function onQueue(string $queue): static { return $this; }
    public function __construct(string $queue) {}
}

final class ResetRoute implements ShouldQueueAfterCommit // @expect laravel.queueRoute
{
    use Queueable;
    public function __construct(string $queue)
    {
        $this->onQueue($queue);
        $this->queue = null;
    }
}

final class BranchRoutes implements ShouldQueueAfterCommit
{
    use Queueable;
    public function __construct(bool $first, string $queue)
    {
        if ($first) {
            $this->onQueue($queue);
            return;
        }
        $this->onQueue($queue);
    }
}

final class PromotedRoute implements ShouldQueueAfterCommit
{
    public function __construct(public string $queue) {}
}

#[Queue('settings')]
final class ConstructorSettings implements ShouldQueueAfterCommit
{
    public int $tries;
    public int $timeout;
    public array $backoff;
    public function __construct()
    {
        $this->tries = 3; // @expect laravel.queueAttribute
        $this->timeout = 30; // @expect laravel.queueAttribute
        $this->backoff = [1, 2]; // @expect laravel.queueAttribute
    }
}

final class PromotedDefault implements ShouldQueueAfterCommit
{
    public function __construct(
        public string $queue = 'promoted', // @expect laravel.queueAttribute
        public int $tries = 3, // @expect laravel.queueAttribute
    ) {}
}

final class RuntimeInterpolation implements ShouldQueueAfterCommit
{
    use Queueable;
    public function __construct(string $tenant)
    {
        $this->onQueue("tenant-$tenant");
    }
}

namespace QueueRoutingFixtures\Listeners;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

final class NullRoute implements ShouldQueueAfterCommit // @expect laravel.queueRoute
{
    public function viaQueue(): ?string { return null; }
}

#[\Illuminate\Queue\Attributes\Queue('ignored')] // @expect laravel.queueRoute
final class NullMethodOverridesAttribute implements ShouldQueueAfterCommit
{
    public function viaQueue(): ?string { return null; }
}

namespace QueueRoutingFixtures\Jobs;

final class HiddenConstructorRoute implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit // @expect laravel.queueRoute
{
    private string $queue;
    public function __construct(string $queue) { $this->queue = $queue; }
}

final class ProtectedConstructorRoute implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit // @expect laravel.queueRoute
{
    protected string $queue;
    public function __construct(string $queue) { $this->queue = $queue; }
}

final class PublicConstructorRoute implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit
{
    public string $queue;
    public function __construct(string $queue) { $this->queue = $queue; }
}

/** @property string $queue */
#[\AllowDynamicProperties]
final class DynamicPublicRoute implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit
{
    public function __construct(string $queue) { $this->queue = $queue; }
}

/** @property string $queue */
#[\AllowDynamicProperties] // @expect laravel.queueRoute
final class InterceptedDynamicRoute implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit
{
    public function __construct(string $queue) { $this->queue = $queue; }
    public function __set(string $name, mixed $value): void {}
}
