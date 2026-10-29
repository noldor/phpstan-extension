<?php

declare(strict_types = 1);

namespace QueueRoutingFixtures\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\Attributes\Tries;

final class Missing implements ShouldQueueAfterCommit {} // @expect laravel.queueRoute

#[Queue('search')]
#[Tries(3)]
class Routed implements ShouldQueueAfterCommit
{
    use Dispatchable, Queueable;
}

final class Inherited extends Routed {}

final class Legacy implements ShouldQueueAfterCommit
{
    public string $queue = 'search'; // @expect laravel.queueAttribute
    public string $connection = 'redis'; // @expect laravel.queueAttribute
    public int $tries = 3; // @expect laravel.queueAttribute
    public int $timeout = 60; // @expect laravel.queueAttribute
    public array $backoff = [5, 10]; // @expect laravel.queueAttribute
}

final class Dynamic implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct(string $queue)
    {
        $this->onQueue($queue);
    }

    public function backoff(): int
    {
        return random_int(1, 10);
    }
}

final class StaticConstructor implements ShouldQueueAfterCommit
{
    use Queueable;

    public function __construct()
    {
        $this->onQueue('search'); // @expect laravel.queueAttribute
    }
}

final class Conditional implements ShouldQueueAfterCommit // @expect laravel.queueRoute
{
    use Queueable;

    public function __construct(bool $route)
    {
        if ($route) {
            $this->onQueue(random_int(1, 2) === 1 ? 'first' : 'second');
        }
    }
}

final class UnsupportedViaQueue implements ShouldQueueAfterCommit // @expect laravel.queueRoute
{
    public function viaQueue(): string { return 'unsupported'; }
}

namespace QueueRoutingFixtures\Listeners;

use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;

final class Missing implements ShouldQueueAfterCommit {} // @expect laravel.queueRoute

final class Dynamic implements ShouldQueueAfterCommit
{
    public function viaQueue(object $event): string { return $event->queue; }
}

final class StaticMethod implements ShouldQueueAfterCommit
{
    public function viaQueue(): string { return 'events'; } // @expect laravel.queueAttribute
    public function tries(): int { return 4; } // @expect laravel.queueAttribute
    public function backoff(): array { return [1, 5]; } // @expect laravel.queueAttribute
}

final class Synchronous implements \Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit {}

namespace QueueRoutingFixtures\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;

final class Missing extends Notification implements ShouldQueueAfterCommit {} // @expect laravel.notificationQueues

final class Routed extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;
    public function via(object $notifiable): array { return ['mail', 'database']; }
    public function viaQueues(): array { return ['mail' => 'mail', 'database' => 'database']; }
}

final class Incomplete extends Notification implements ShouldQueueAfterCommit
{
    public function via(object $notifiable): array { return ['mail', 'database']; }
    public function viaQueues(): array { return ['mail' => 'mail']; } // @expect laravel.notificationQueues
}

final class EmptyMap extends Notification implements ShouldQueueAfterCommit
{
    public function viaQueues(): array { return []; } // @expect laravel.notificationQueues
}

#[Queue('fallback')]
final class Fallback extends Notification implements ShouldQueueAfterCommit
{
    public function via(object $notifiable): array { return ['mail', 'database']; }
    public function viaQueues(): array { return ['mail' => 'mail']; }
}

#[Queue('fallback')]
final class EmptyEntry extends Notification implements ShouldQueueAfterCommit
{
    public function viaQueues(): array { return ['mail' => '']; } // @expect laravel.notificationQueues
}

#[Queue('fallback')]
final class NullEntry extends Notification implements ShouldQueueAfterCommit
{
    public function viaQueues(): array { return ['mail' => null]; }
}
