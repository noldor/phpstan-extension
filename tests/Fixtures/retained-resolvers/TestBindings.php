<?php

declare(strict_types=1);

namespace Retained;

function testOnlyBindings(\Illuminate\Container\Container $app): void
{
    $app->bind(\Retained\Services\ReviewedFactory::class, \Illuminate\Http\Request::class);
}
