<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Illuminate\Foundation\Http\FormRequest;
use Nuwave\Lighthouse\Validation\Validator;

final class MisplacedRequest extends FormRequest
{
    public function rules(): array
    {
        return ['name' => 'required'];
    }
}

final class MisplacedGraphqlValidator extends Validator
{
    public function rules(): array
    {
        return ['name' => 'required'];
    }
}
