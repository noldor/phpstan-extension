<?php

declare(strict_types = 1);

namespace DependencyAccess\Helpers;

use Illuminate\Container\Container;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Config;
use function resolve as acquire;

function custom_service(): void {}
function resolve(): void {}

class Selected
{
    public function __construct()
    {
        \resolve(Guard::class);
        $callable = \resolve(...);
    }

    public function __invoke(Container $container): void
    {
        \resolve(Guard::class);
        acquire(Guard::class);
        $callable = \resolve(...);
        $alias = acquire(...);
        \app(Guard::class);
        \config('app.name');
        Config::get('app.name');
        $container->make(Guard::class);
        $make = $container->make(...);
        $service = $container[Guard::class];
        Container::getInstance();
        custom_service(); resolve();
        $closure = static function (): void { \resolve(Guard::class); };
        $arrow = static fn () => \resolve(Guard::class);
        $anonymous = new class {
            public function __invoke(): void { \resolve(Guard::class); }
        };
    }

    public function other(): void
    {
        \resolve(Guard::class);
        $callable = \resolve(...);
    }
}

final class Child extends Selected
{
    public function __invoke(Container $container): void { \resolve(Guard::class); }
}

final class Other
{
    public function __invoke(): void { \resolve(Guard::class); }
}

namespace DependencyAccess\Helpers\Resolvers;

final class Mutation
{
    public function __construct() { \resolve(\stdClass::class); }
    public function __invoke(): void { \resolve(\stdClass::class); }
    public function other(): void { \resolve(\stdClass::class); }
}

namespace DependencyAccess\Helpers\ResolversNeighbour;

final class Mutation
{
    public function __invoke(): void { \resolve(\stdClass::class); }
}

namespace DependencyAccess\Helpers\OtherArea;

final class Custom
{
    public function run(): void { \DependencyAccess\Helpers\custom_service(); }
    public function __invoke(): void { \DependencyAccess\Helpers\custom_service(); }
}
