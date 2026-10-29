<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Can;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\AnyOf;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\Rules\Email;
use Illuminate\Validation\Rules\ImageFile;
use Illuminate\Validation\Rules\Password;
use Nuwave\Lighthouse\Validation\Validator;

enum NativeMessageEnum: string { case Value = 'value'; }

final class NativeObjectMessages
{
    public function rules(): array
    {
        return [
            'permission' => [Rule::can('update')], 'status' => [Rule::enum(NativeMessageEnum::class)],
            'choice' => [Rule::anyOf([['integer'], ['email']])], 'file' => [Rule::file()->min(3)],
            'image' => [File::image()->dimensions(Rule::dimensions()->minWidth(3))],
            'mail' => [Rule::email()->rules(['min:3'])], 'password' => [Password::min(8)->letters()],
            'text' => ['bail', 'string', Rule::enum(NativeMessageEnum::class)],
            'number' => ['numeric', Rule::enum(NativeMessageEnum::class)],
            'unsafe' => [Rule::enum(NativeMessageEnum::class)],
        ];
    }
    public function messages(): array
    {
        return [
            'permission.' . Can::class => 'Denied.', 'status.' . Enum::class => 'Unknown.', 'choice.' . AnyOf::class => 'Invalid.',
            'file.' . File::class => 'Invalid file.', 'image.' . ImageFile::class => 'Invalid image.',
            'mail.' . Email::class => 'Invalid email.', 'password.' . Password::class => 'Invalid password.',
            'text.string' => 'String.', 'text.enum' => 'Unknown.', 'number.numeric' => 'Number.', 'number.enum' => 'Unknown.',
            'unsafe.enum' => 'Unknown.',
        ];
    }
}

final class GenericNativeObjectMessage extends Validator
{
    public function rules(): array { return []; }
    public function messages(): array { return [Enum::class => 'Unknown.', File::class => 'Invalid file.', 'status.' . Enum::class => 'Unknown.']; }
}
