<?php

declare(strict_types=1);

namespace RetainedCache;

interface Contract {}
class Safe implements Contract {}
class Unsafe implements Contract
{
    public function __construct(private readonly \Illuminate\Http\Request $request) {}
}
