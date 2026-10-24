<?php

declare(strict_types = 1);

namespace Consumer\Domain;

use Illuminate\Validation\Rules\Password;

final class Excluded
{
    public function run(): void
    {
        new Password(12);
    }
}
