<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Contracts\Validation\Factory;

final class CallableAllowed
{
    public function run(Factory $factory): void
    {
        \validator(...);
        Validator::make(...);
        $factory->make(...);
        new Password(...);
    }
}
