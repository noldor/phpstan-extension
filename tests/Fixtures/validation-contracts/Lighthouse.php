<?php

namespace ValidationContractFixtures;

use Nuwave\Lighthouse\Validation\Validator;

final class GenericMessage extends Validator
{
    public function rules(): array { return ['ownerId' => ['nullable']]; }
    public function messages(): array { return ['exists' => 'Not found.']; }
}

final class InputBound extends Validator
{
    public function rules(): array { return ['input.ownerId' => ['nullable']]; }
    public function messages(): array { return ['input.ownerId.integer' => 'Use an integer.']; }
    public function attributes(): array { return ['input.ownerId' => 'Owner']; }
}

final class ActualNestedInput extends Validator
{
    public function rules(): array { return ['input.ownerId' => ['nullable']]; }
    public function messages(): array { return ['input.ownerId.integer' => 'Use an integer.']; }
    public function attributes(): array { return ['input.ownerId' => 'Owner']; }
}

final class FieldBound extends Validator
{
    public function rules(): array { return ['input.ownerId' => ['nullable']]; }
    public function messages(): array { return ['input.ownerId.integer' => 'Use an integer.']; }
    public function attributes(): array { return ['input.ownerId' => 'Owner']; }
}
