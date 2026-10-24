<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Illuminate\Support\Facades\Validator;

final class InvalidDirectory
{
    public function run(): void
    {
        Validator::make([], ['name' => 'required']);
    }
}
