<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use ValidationFixtures\AdministratorPassword;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Support\Facades\Validator as ValidatorFacade;

final class MoreInlineValidation
{
    public function run(Factory $factory, Validator $validator, Request|Unrelated $request): void
    {
        RequestFacade::validate(['name' => 'required']);
        RequestFacade::validateWithBag('default', ['name' => 'required']);
        new AdministratorPassword();
        ValidatorFacade::make(...);
        $factory->make(...);
        $validator->after(fn () => null);
        $factory->extend('custom', fn () => true);
        $facade = ValidatorFacade::class;
        $facade::make([], ['name' => 'required']);
        $request->validate(['name' => 'required']);
        Rule::in(...);
        \validator(...);
        \validator()->make([], ['name' => 'required']);
        new AdministratorPassword(...);
    }
}
