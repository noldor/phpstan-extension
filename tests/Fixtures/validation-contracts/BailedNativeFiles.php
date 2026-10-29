<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\ImageFile;

final class BailedNativeFiles
{
    public function rules(): array
    {
        return [
            'upload' => ['bail', Rule::file(), 'max:0', 'gt:10'],
            'image' => ['bail', File::image(), 'max:0', 'gt:10'],
            'unguarded' => [Rule::file(), 'max:0', 'gt:10'],
            'before' => ['bail', 'max:0', Rule::file()],
        ];
    }
    public function messages(): array
    {
        return [
            'upload.' . File::class => 'Invalid file.',
            'upload.max' => ['file' => 'Too large.'], 'upload.gt' => ['file' => 'Too small.'],
            'image.' . ImageFile::class => 'Invalid image.',
            'image.max' => ['file' => 'Too large.'], 'image.gt' => ['file' => 'Too small.'],
            'unguarded.' . File::class => 'Invalid file.',
            'unguarded.max' => ['file' => 'Too large.'], 'unguarded.gt' => ['file' => 'Too small.'],
            'before.max' => ['file' => 'Too large.'], 'before.' . File::class => 'Invalid file.',
        ];
    }
}
