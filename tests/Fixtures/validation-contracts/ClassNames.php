<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rules\Enum;

final class MessageClassNames
{
    public function rules(): array { return []; }
    public function messages(): array
    {
        return [
            'status.' . Enum::class => 'Unknown status.',
            'literal.Illuminate\\Validation\\Rules\\Enum' => 'Unknown status.',
            'nested' => [Enum::class => 'Unknown status.'],
            'value.required' => 'Rejected by ' . Enum::class . '.',
            'other.required' => 'ValidationContractFixtures\\OwnedMessage rejected the value.',
            'global.required' => 'RuntimeException rejected the value.',
            'plain.required' => 'File is required; enum values are accepted.',
            'path.required' => 'Use C:\\documents\\upload.txt.',
            'translation.required' => __('validation.required'),
            'case.required' => 'illuminate\\validation\\rules\\enum rejected the value.',
            'basename.required' => 'Enum is not a public status description.',
        ];
    }
}
