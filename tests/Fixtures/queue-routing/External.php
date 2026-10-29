<?php

declare(strict_types = 1);

namespace QueueRoutingFixtures;

use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Queue\QueueRoutes;
use Illuminate\Foundation\Bus\PendingChain;
use QueueRoutingFixtures\Jobs\Routed;
use QueueRoutingFixtures\Notifications\Routed as Notification;

function external(Routed $job, Notification $notification, QueueRoutes $routes, \Illuminate\Queue\QueueManager $manager): void
{
    $job->onQueue('other'); // @expect laravel.externalQueueRoute
    $job->onConnection('other'); // @expect laravel.externalQueueRoute
    $job->allOnQueue('other'); // @expect laravel.externalQueueRoute
    $job->queue = 'other'; // @expect laravel.externalQueueRoute
    $job->{'queue'} = 'other'; // @expect laravel.externalQueueRoute
    $job->queue .= '-other'; // @expect laravel.externalQueueRoute
    $queueReference =& $job->queue; // @expect laravel.externalQueueRoute
    $job->queue[0] = 'x'; // @expect laravel.externalQueueRoute
    $job->connection = 'other'; // @expect laravel.externalQueueRoute
    $job->onQueue(...); // @expect laravel.externalQueueRoute
    $notification->onQueue('other'); // @expect laravel.externalQueueRoute
    Routed::dispatch()->onQueue('other'); // @expect laravel.externalQueueRoute
    dispatch(new Routed())->onConnection('redis'); // @expect laravel.externalQueueRoute
    Routed::dispatch()->delay(1)->allOnQueue('other'); // @expect laravel.externalQueueRoute
    Bus::chain([new Routed()])->onQueue('other'); // @expect laravel.externalQueueRoute
    Bus::batch([new Routed()])->onConnection('redis'); // @expect laravel.externalQueueRoute
    Bus::batch([])->add([new Routed()])->onQueue('other'); // @expect laravel.externalQueueRoute
    (new PendingChain(new Routed(), []))->onQueue('other'); // @expect laravel.externalQueueRoute
    Queue::route(Routed::class, 'other'); // @expect laravel.externalQueueRoute
    $routes->set([Routed::class => 'other']); // @expect laravel.externalQueueRoute
    Queue::route(\Illuminate\Contracts\Queue\ShouldQueue::class, 'other'); // @expect laravel.externalQueueRoute
    Queue::forward('search', 'other'); // @expect laravel.externalQueueRoute
    $routes->forward(['mail' => 'other']); // @expect laravel.externalQueueRoute
    Queue::forward('unrelated', 'other');
    Queue::pushOn('other', new Routed()); // @expect laravel.externalQueueRoute
    Queue::push(new Routed(), '', 'other'); // @expect laravel.externalQueueRoute
    $job->delay(1);
    $job->delay(1)->onQueue('other'); // @expect laravel.externalQueueRoute
    (new \QueueRoutingFixtures\Jobs\NativeOverride('route'))->onQueue('unrelated-method');
    Queue::push(new Routed());
    $manager->push(new Routed(), '', 'other'); // @expect laravel.externalQueueRoute
    Queue::connection('redis')->push(new Routed()); // @expect laravel.externalQueueRoute
    Queue::connection()->push(new Routed());
}

final class Unrelated
{
    public function onQueue(string $name): void {}
    public function dispatch(): self { return $this; }
}

function unrelated(Unrelated $service): void
{
    $service->onQueue('ok');
    $service->dispatch()->onQueue('ok');
}
