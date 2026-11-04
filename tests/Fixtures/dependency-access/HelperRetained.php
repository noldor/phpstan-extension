<?php

declare(strict_types = 1);

namespace DependencyAccess\Retained;

final class Mutation
{
    public function __construct(private readonly \Illuminate\Http\Request $request)
    {
        \resolve(\stdClass::class);
    }

    public function __invoke(): void
    {
        \resolve(\stdClass::class);
    }
}
