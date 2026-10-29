<?php

namespace ValidationContractFixtures;

final class InvalidSizeBranches
{
    public function rules(): array { return ['name' => ['string', 'max:5'], 'required' => ['required'], 'nullValue' => ['required']]; }
    public function messages(): array
    {
        return ['name.string' => 'Text.', 'name.max' => ['numeric' => 'Number too large.'], 'required.required' => ['unused' => 'Unused.'], 'nullValue.required' => null];
    }
}

final class ValidSizeBranches
{
    public function rules(): array
    {
        return [
            'number' => ['numeric', 'max:5'],
            'items' => ['array', 'min:2'],
            'name' => ['bail', 'string', 'max:5'],
            'upload' => ['bail', 'file', 'max:5'],
            'unknown' => ['max:5'],
            'scalar' => ['max:5'],
        ];
    }
    public function messages(): array
    {
        return [
            'number.numeric' => 'Number.', 'number.max' => ['numeric' => 'Too large.'],
            'items.array' => 'List.', 'items.min' => ['array' => 'Too short.'],
            'name.string' => 'Text.', 'name.max' => ['string' => 'Too long.'],
            'upload.file' => 'File.', 'upload.max' => ['file' => 'Too large.'],
            'unknown.max' => ['string' => 'Too long.', 'file' => 'Too large.'],
            'scalar.max' => 'Too large.',
        ];
    }
}

final class SizeBeforeType
{
    public function rules(): array { return ['name' => ['bail', 'max:5', 'string']]; }
    public function messages(): array { return ['name.max' => ['string' => 'Too long.'], 'name.string' => 'Text.']; }
}
