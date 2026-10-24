<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Illuminate\Validation\Rules\Password;

final class CustomPassword extends Password
{
    public static function allowed(string $value): bool
    {
        return '' !== $value;
    }
}
