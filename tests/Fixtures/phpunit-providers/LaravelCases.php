<?php

declare(strict_types=1);

namespace ProviderFixtures\Laravel;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Illuminate\Contracts\Queue\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Record extends Model {}

/** @extends Factory<Record> */
final class RecordFactory extends Factory
{
    protected $model = Record::class;

    public function definition(): array
    {
        return [];
    }
}

final class LaravelProviderTest extends TestCase
{
    #[DataProvider('databaseCases')]
    #[DataProvider('deliveryCases')]
    public function testCases(mixed $value): void {}

    public static function databaseCases(): array
    {
        DB::select('SELECT 1'); // @expect phpunit.providerSideEffect
        Record::query()->exists(); // @expect phpunit.providerSideEffect
        RecordFactory::new()->create(); // @expect phpunit.providerSideEffect
        RecordFactory::new()->createQuietly(); // @expect phpunit.providerSideEffect
        RecordFactory::new()->insert(); // @expect phpunit.providerSideEffect
        Record::query()->firstOrCreate(['name' => 'fixture']); // @expect phpunit.providerSideEffect
        Record::query()->createOrFirst(['name' => 'fixture']); // @expect phpunit.providerSideEffect
        Record::query()->updateOrCreate(['name' => 'fixture']); // @expect phpunit.providerSideEffect
        Record::query()->upsert([['name' => 'fixture']], 'name'); // @expect phpunit.providerSideEffect
        Record::query()->forceCreate(['name' => 'fixture']); // @expect phpunit.providerSideEffect
        $query = Record::query()->getQuery();
        $query->sum('total'); // @expect phpunit.providerSideEffect
        $query->min('total'); // @expect phpunit.providerSideEffect
        $query->max('total'); // @expect phpunit.providerSideEffect
        $query->avg('total'); // @expect phpunit.providerSideEffect
        $query->insertGetId(['name' => 'fixture']); // @expect phpunit.providerSideEffect
        $query->updateOrInsert(['name' => 'fixture'], ['total' => 1]); // @expect phpunit.providerSideEffect
        $query->upsert([['name' => 'fixture']], 'name'); // @expect phpunit.providerSideEffect
        $query->truncate(); // @expect phpunit.providerSideEffect

        return [[static fn () => RecordFactory::new()->create(), RecordFactory::new()->make()]];
    }

    public static function deliveryCases(): array
    {
        \dispatch(new \stdClass()); // @expect phpunit.providerSideEffect
        \dispatch_sync(new \stdClass()); // @expect phpunit.providerSideEffect
        \event(new \stdClass()); // @expect phpunit.providerSideEffect
        [$bus, $events, $queue, $mailer, $notifications] = self::transports();
        $bus->dispatch(new \stdClass()); // @expect phpunit.providerSideEffect
        $events->dispatch(new \stdClass()); // @expect phpunit.providerSideEffect
        $queue->push(new \stdClass()); // @expect phpunit.providerSideEffect
        $mailer->send('fixture'); // @expect phpunit.providerSideEffect
        $notifications->send([], new \stdClass()); // @expect phpunit.providerSideEffect

        return [[static fn () => $queue->push(new \stdClass())]];
    }

    /** @return array{BusDispatcher, EventDispatcher, Queue, Mailer, NotificationDispatcher} */
    private static function transports(): array
    {
        throw new \LogicException('Static-analysis fixture; never executed.');
    }
}
