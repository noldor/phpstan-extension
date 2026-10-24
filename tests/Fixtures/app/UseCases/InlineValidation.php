<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator as LaravelValidator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

use function validator as makeValidator;

final class InlineValidation
{
    use ValidatesRequests;

    public function run(Factory $factory, Request $request, Validator $validator, ValidatorContract $contract): void
    {
        $data = [];
        $rules = ['name' => ['required', 'string']];

        LaravelValidator::make($data, $rules);
        LaravelValidator::validate($data, $rules);
        $factory->make($data, $rules);
        \validator($data, $rules);
        makeValidator($data, $rules);
        $request->validate($rules);
        $request?->validateWithBag('default', $rules);
        $this->validate($request, $rules);
        $this->validateWithBag('default', $request, $rules);
        $this->validateWith($rules, $request);
        new Validator(\app('translator'), $data, $rules);
        Rule::in(['yes']);
        Password::min(12);
        new Password(12);
        $validator->setRules($rules);
        $validator->addRules($rules);
        $contract->sometimes('name', 'required', fn () => true);
    }

    public function validateMaybe(array|ValidatorContract $validator): void
    {
        $this->validateWith($validator);
    }
}
