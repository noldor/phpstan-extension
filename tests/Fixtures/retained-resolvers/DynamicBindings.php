<?php

declare(strict_types=1);

namespace Retained;

function dynamicBindings(\Illuminate\Container\Container $app, string $abstract): void
{
    $app->bind($abstract, \Retained\Services\SafeService::class);
}
function globalAttributeHooks(\Illuminate\Container\Container $app): void
{
    $app->whenHasAttribute(\Attribute::class, static fn () => null);
    $app->afterResolvingAttribute(\Attribute::class, static fn () => null);
}
function unknownAliasTarget(\Illuminate\Container\Container $app, string $abstract): void
{
    $app->alias($abstract, 'unknown.alias');
}
