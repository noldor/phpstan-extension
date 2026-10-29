<?php

namespace ValidationContractFixtures;

use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;

final class BusinessCheck implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void { $fail('Public business failure.'); }
}

final class MissingMessages
{
    public function rules(): array
    {
        return [
            'title' => ['bail', 'required', 'string', 'max:255'],
            'ownerId' => ['nullable', Rule::exists('users', 'id')],
            'custom' => [new BusinessCheck()],
            'conditional' => ['sometimes', 'nullable', 'exclude_if:enabled,false'],
        ];
    }
    public function messages(): array
    {
        return ['title.required' => __('validation.required'), 'title.string' => 'Use text.'];
    }
}
