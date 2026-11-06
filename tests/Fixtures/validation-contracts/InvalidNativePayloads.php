<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class InvalidNativePayloads
{
    public function rules(): array
    {
        return [
            'empty' => ['bail', 'string', Rule::enum(NativeMessageEnum::class)],
            'nested' => ['bail', 'string', Rule::enum(NativeMessageEnum::class)],
            'zero' => ['bail', 'string', Rule::enum(NativeMessageEnum::class)],
            'list' => ['bail', 'string', Rule::enum(NativeMessageEnum::class)],
        ];
    }
    public function messages(): array
    {
        return [
            'empty.string' => 'String.', 'empty.enum' => [],
            'nested.string' => 'String.', 'nested.enum' => [[]],
            'zero.string' => 'String.', 'zero.enum' => '0',
            'list.string' => 'String.', 'list.enum' => ['One.', 'Two.'],
            'forbidden.' . Enum::class => [],
        ];
    }
}
