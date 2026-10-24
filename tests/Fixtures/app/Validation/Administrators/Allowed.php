<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

final class Allowed3
{
    public function run(array $data): void
    {
        Validator::make($data, ['name' => ['required', Rule::in(['yes'])], 'password' => Password::min(12)])->validate();
        \validator($data, ['name' => 'required'])->validate();
    }
}
