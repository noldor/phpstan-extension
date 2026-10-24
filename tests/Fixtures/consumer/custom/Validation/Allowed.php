<?php

declare(strict_types = 1);

namespace CustomConsumer\Validation;

use Illuminate\Validation\Rules\Password;

final class Allowed
{
    public function run(): void
    {
        new Password(12);
    }
}
