<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Dimensions;
use Illuminate\Validation\Rules\Numeric;

final class MissingBuiltinBuilderMessages
{
    public function rules(): array
    {
        return ['name' => [Rule::string()->min(3)], 'picture' => [Rule::dimensions(['min_width' => 100])], 'count' => [Rule::numeric()]];
    }
    public function messages(): array { return ['name.string' => 'Text.']; }
}

final class CompoundBuiltinBuilders
{
    public function rules(): array
    {
        return [
            'mail' => [Rule::email()->rules(['max:255'])],
            'date' => [Rule::date()->format('Y-m-d')->beforeToday()->betweenOrEqual('2026-01-01', '2026-12-31')],
            'ip' => [Rule::ip()->version(4)],
            'text' => [Rule::string()->alphaNumeric()->min(3)->max(8)],
            'number' => [Rule::numeric()->integer()->greaterThan('other')->minDigits(2)],
            'file' => [File::types(['png', 'image/png'])->extensions(['png'])->min(1)->max(4)->encoding('utf-8')],
            'image' => [File::image()->dimensions(Rule::dimensions()->minWidth(10))],
            'password' => [Password::min(8)->max(16)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
            'direct' => [new Dimensions(['min_width' => 10]), new Numeric()],
            'required' => [Rule::requiredIf(true)],
            'requiredUnless' => [Rule::requiredUnless(false)],
            'prohibited' => [Rule::prohibitedIf(true)],
            'prohibitedUnless' => [Rule::prohibitedUnless(false)],
            'inactive' => [Rule::requiredIf(false), Rule::requiredUnless(true), Rule::prohibitedIf(false), Rule::prohibitedUnless(true)],
        ];
    }
}
