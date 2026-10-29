<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Password;

final class BuiltinBoundaries
{
    public function rules(): array
    {
        return [
            'fileSize' => [File::types([])->min('1mb')->max('1000kb')],
            'emptyOptions' => [Rule::file()->extensions(['png'])->extensions([])->encoding('')],
            'password' => [Password::min(8)->max(16)->max(0)->rules(['required'])->rules(['same:confirmation'])],
        ];
    }
    public function messages(): array
    {
        return ['fileSize.file' => 'File.', 'fileSize.size' => 'Size.', 'emptyOptions.file' => 'File.', 'password.string' => 'Text.', 'password.min' => 'Too short.', 'password.same' => 'Does not match.'];
    }
}

final class NestedContext
{
    public function rules(): array { return ['upload' => ['numeric', File::image()->max(1)]]; }
    public function messages(): array { return ['upload.numeric' => 'Number.', 'upload.file' => 'File.', 'upload.image' => 'Image.', 'upload.max' => ['numeric' => 'Too large.']]; }
}

final class RuleListComposition
{
    public function rules(): array
    {
        return [
            'override' => [0 => Rule::numeric(), 0 => Rule::string()->min(3)],
            'merged' => ['required'] + ['string'],
            'mergedBuiltin' => [0 => 'bail'] + [1 => Rule::string()->min(3)],
            'conditional' => Rule::when(true, ['required', 'min:2'], ['numeric']),
        ];
    }
    public function messages(): array
    {
        return ['override.string' => 'Text.', 'override.min' => 'Too short.', 'merged.required' => 'Required.', 'mergedBuiltin.string' => 'Text.', 'conditional.required' => 'Required.'];
    }
}
