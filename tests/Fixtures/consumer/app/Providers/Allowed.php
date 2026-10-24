<?php

declare(strict_types = 1);

namespace Consumer\Providers;

use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Validator;

final class Allowed
{
    public function run(): void
    {
        new Password(12);
        Validator::make(...);
    }
}
