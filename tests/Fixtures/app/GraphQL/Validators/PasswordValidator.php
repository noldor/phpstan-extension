<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Nuwave\Lighthouse\Validation\Validator;

final class PasswordValidator extends Validator
{
    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return ['password' => 'required'];
    }
}
