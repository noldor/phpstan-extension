<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class AdministratorPassword implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void {}
}
