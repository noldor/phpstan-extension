<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;

final class PlainNativeAliases
{
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(NativeMessageEnum::class)],
            'statuses' => ['array'],
            'statuses.*' => [Rule::enum(NativeMessageEnum::class)],
            'permission' => [Rule::can('update')],
            'choice' => [Rule::anyOf([['integer'], ['email']])],
        ];
    }
    public function messages(): array
    {
        return [
            'status.required' => 'Required.', 'status.enum' => 'Unknown status.',
            'statuses.array' => 'List.', 'statuses.*.enum' => 'Unknown status.',
            'permission.can' => 'Denied.', 'choice.any_of' => 'Invalid choice.',
        ];
    }
}

final class FileNativeAliases
{
    public function rules(): array
    {
        return [
            'upload' => ['file', Rule::enum(NativeMessageEnum::class)],
            'image' => ['image', Rule::enum(NativeMessageEnum::class)],
            'builder' => [Rule::file(), Rule::enum(NativeMessageEnum::class)],
            'mime' => ['mimes:txt', Rule::enum(NativeMessageEnum::class)],
            'numeric' => ['numeric', 'file', Rule::enum(NativeMessageEnum::class)],
            'text' => ['bail', 'string', 'file', Rule::enum(NativeMessageEnum::class)],
        ];
    }
    public function messages(): array
    {
        return [
            'upload.file' => 'Invalid upload.', 'upload.enum' => 'Unknown upload.',
            'image.image' => 'Invalid image.', 'image.enum' => 'Unknown image.',
            'builder.file' => 'Invalid upload.', 'builder.enum' => 'Unknown upload.',
            'mime.mimes' => 'Invalid type.', 'mime.enum' => 'Unknown upload.',
            'numeric.numeric' => 'Number.', 'numeric.file' => 'Invalid upload.', 'numeric.enum' => 'Unknown value.',
            'text.string' => 'Text.', 'text.file' => 'Invalid upload.', 'text.enum' => 'Unknown value.',
        ];
    }
}
