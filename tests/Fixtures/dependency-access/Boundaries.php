<?php

declare(strict_types = 1);

namespace DependencyAccess\Boundaries;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

function auth(): Guard { throw new \LogicException('Fixture signature only.'); }

final class CustomTransport {}

final class Registry
{
    public function fetch(string $key): object { return new \stdClass; }
}

final class BoundaryUseCase
{
    public function __construct(private readonly Guard $guard) {}

    public function run(CustomTransport $arguments, Registry $registry, Container $container): void
    {
        $globalUser = 'USER';
        Auth::{$globalUser}();
        $name = Auth::class;
        $name::id();
        $registry->fetch('service');
        $read = $registry->fetch(...);
        $container->offsetGet('service');
        $container['service'] = new \stdClass;
        Auth::check();
        $this->guard->user();
        auth()->user();
    }
}

final class Excluded
{
    public function run(Request $request): void { \app('service'); Auth::user(); }
}

final class DynamicFacadeService
{
    public function run(): void
    {
        $facade = Auth::class;
        $lookup = $facade::user(...);
        $method = 'id';
        $lookup = Auth::{$method}(...);
    }
}
