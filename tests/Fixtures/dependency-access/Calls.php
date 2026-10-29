<?php

declare(strict_types = 1);

namespace DependencyAccess;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Auth as Actor;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

function collect(array $items): array { return $items; }
function config(string $key): string { return $key; }

final class SettingsFacade extends Facade
{
    public static function read(): string { return 'setting'; }
}

final class Service
{
    public function __construct(private readonly Guard $guard) {}

    public function run(ContainerContract $container, ContainerInterface $psr, Container|null $nullable): void
    {
        Config::get('app.name');
        Actor::user();
        SettingsFacade::read();
        \config('app.name');
        \app(Guard::class);
        \resolve(Guard::class);
        $container->make(Guard::class);
        $container->get(Guard::class);
        $psr->get(Guard::class);
        $nullable?->make(Guard::class);
        Container::getInstance();
        $make = $container->make(...);
        $helper = \app(...);
        $facade = SettingsFacade::read(...);
        $service = $container[Guard::class];
        $native = \mb_strlen('allowed');
        $pure = \collect([]);
        $local = collect([]);
        $custom = config('local');
        $this->guard->user();
        $array = ['key' => 'value'];
        $value = $array['key'];
    }
}

final class UseCase
{
    public function __construct(private readonly Request $request) {}

    /** @param list<Request> $requests */
    public function handle(Request $request, array $requests, Guard $guard): Request
    {
        Actor::user();
        Actor::id();
        \auth()->user();
        Actor::guard('api')->user();
        \auth('api')?->id();
        $actor = Actor::user(...);
        $actor = \auth()->user(...);
        $actor = $guard->user();
        return $request;
    }
}

final class AllowedModel extends Model
{
    public function readSetting(): void { Config::get('app.name'); \app(Guard::class); }
}

final class AllowedMigration extends Migration
{
    public function up(): void { Config::get('app.name'); }
}

final class AllowedProvider extends ServiceProvider
{
    public function boot(): void { Config::get('app.name'); }
}

final class AllowedTest extends TestCase
{
    public function testExample(): void { Config::get('app.name'); }
}
