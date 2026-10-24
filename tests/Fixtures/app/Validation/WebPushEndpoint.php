<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class WebPushEndpoint implements ValidationRule
{
    public static function allowed(string $endpoint): bool
    {
        return \str_starts_with($endpoint, 'https://');
    }

    public static function create(): self
    {
        return new self();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void {}
}
