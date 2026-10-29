<?php

namespace ValidationContractFixtures;

final class WrongMethods
{
    public function attributes(): array { return []; }
    public function rules(): array { return []; }
    public function messages(): array { return []; }
}
