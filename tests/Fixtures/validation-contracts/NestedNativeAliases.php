<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;

final class NestedNativeAliases
{
    public function rules(): array { return ['status' => ['bail', 'string', Rule::enum(NativeMessageEnum::class)]]; }
    public function messages(): array { return ['status' => ['string' => 'String.', 'enum' => 'Unknown.']]; }
}
