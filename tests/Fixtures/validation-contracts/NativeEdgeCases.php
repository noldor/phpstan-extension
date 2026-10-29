<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;
use Nuwave\Lighthouse\Validation\Validator;

final class ImplicitNumericConstraints
{
    public function rules(): array
    {
        return ['digits' => [Rule::numeric()->digits(3)], 'range' => [Rule::numeric()->digitsBetween(2, 3)], 'exact' => [Rule::numeric()->exactly(3)]];
    }
    public function messages(): array
    {
        return ['digits.numeric' => 'Number.', 'digits.digits' => 'Three digits.', 'range.numeric' => 'Number.', 'range.digits_between' => 'Two or three digits.', 'exact.numeric' => 'Number.', 'exact.size' => 'Exactly three.'];
    }
}

final class EmptyRuleTokens
{
    public function rules(): array { return ['optional' => '', 'list' => [''], 'pipes' => '|nullable|']; }
    public function messages(): array { return []; }
}

final class FalseMessage
{
    public function rules(): array { return ['name' => ['required']]; }
    public function messages(): array { return ['name.required' => false]; }
}

final class GenericPasswordMessages extends Validator
{
    public function rules(): array { return []; }
    public function messages(): array
    {
        return ['password.mixed' => 'Mixed case.', 'password.letters' => 'Letters.', 'password.numbers' => 'Numbers.', 'password.symbols' => 'Symbols.', 'password.uncompromised' => 'Safe password.', 'secret.password.mixed' => 'Mixed case for secret.'];
    }
}
