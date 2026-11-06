<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;

final class ClassFreeNativeMessages
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(NativeMessageEnum::class)],
            'statuses' => ['array'],
            'statuses.*' => ['string', Rule::enum(NativeMessageEnum::class)],
            'wildcard' => [Rule::enum(NativeMessageEnum::class)],
        ];
    }
    public function messages(): array
    {
        return [
            'status.required' => 'Required.', 'status.string' => 'String.', 'status' => 'Unknown status.',
            'statuses.array' => 'List.', 'statuses.*.string' => 'String.', 'statuses.*' => 'Unknown status.',
            'wildcard.*' => 'Unknown status.',
        ];
    }
}
