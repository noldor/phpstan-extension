<?php

declare(strict_types = 1);

namespace Consumer\Validation;

use Illuminate\Validation\Rules\Password;

final class Allowed
{
    public function run(): void
    {
        new Password(12);
    }
}
