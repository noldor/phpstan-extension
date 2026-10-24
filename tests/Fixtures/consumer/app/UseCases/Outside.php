<?php

declare(strict_types = 1);

namespace Consumer\UseCases;

use Illuminate\Validation\Rules\Password;

final class Outside
{
    public function run(): void
    {
        new Password(12);
    }
}
