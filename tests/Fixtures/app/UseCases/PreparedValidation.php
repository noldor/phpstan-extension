<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Validation\ValidatesRequests;
use ValidationFixtures\PasswordValidator;

final class PreparedValidation
{
    use ValidatesRequests;

    public function run(Validator $validator, FormRequest $request, Unrelated $other): void
    {
        $validator->validate();
        $validator->validated();
        $validator->fails();
        $request->validated();
        $request->validateResolved();
        $this->validateWith($validator);
        new PasswordValidator();
        $other->validate(['name' => 'required']);
        $other->make([]);
        \validator();
        $validator->validate(...);
        $other->validate(...);
        new PasswordValidator(...);
    }
}

final class Unrelated
{
    public function validate(array $rules): void {}

    public function make(array $rules): void {}
}
