<?php

declare(strict_types=1);

namespace RetainedCache;

class Root
{
    public function __construct(private readonly Contract $service) {}
}
