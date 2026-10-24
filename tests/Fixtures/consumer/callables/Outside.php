<?php

declare(strict_types = 1);

namespace Consumer\Callables;

use Illuminate\Validation\Rules\Password;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Validator;

final class Outside
{
    public function run(Factory $factory): void
    {
        new Password(12);
        \validator(...);
        Validator::make(...);
        $factory->make(...);
    }
}
