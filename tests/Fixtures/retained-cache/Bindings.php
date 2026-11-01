<?php

declare(strict_types=1);

namespace RetainedCache;

function bindings(\Illuminate\Container\Container $app): void
{
    $app->bind(Contract::class, Safe::class);
}
