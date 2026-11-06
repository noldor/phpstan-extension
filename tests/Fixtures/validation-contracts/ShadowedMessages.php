<?php

namespace ValidationContractFixtures;

use Illuminate\Validation\Rule;

final class SizeMapShadowsFieldFallback
{
    public function rules(): array { return ['name' => ['string', 'max:0']]; }
    public function messages(): array
    {
        return ['name.string' => 'Text.', 'name' => 'Public fallback.', 'name.max' => ['string' => 'String max.']];
    }
}

final class FalseyNativeBuilderOverrides
{
    public function rules(): array
    {
        return ['empty' => [Rule::file()], 'false' => [Rule::file()], 'zero' => [Rule::file()], 'zeroText' => [Rule::file()], 'null' => [Rule::file()], 'map' => [Rule::file()]];
    }
    public function messages(): array
    {
        return [
            'empty.file' => 'Invalid file.', 'empty' => '',
            'false.file' => 'Invalid file.', 'false' => false,
            'zero.file' => 'Invalid file.', 'zero' => 0,
            'zeroText.file' => 'Invalid file.', 'zeroText' => '0',
            'null.file' => 'Invalid file.', 'null' => null,
            'map.file' => 'Invalid file.', 'map' => [],
        ];
    }
}

final class SizeMapShadowsLaterWildcard
{
    public function rules(): array { return ['name' => ['string', 'max:0']]; }
    public function messages(): array
    {
        return ['name.string' => 'Text.', 'name.max' => ['string' => 'String max.'], 'name.*' => 'Public fallback.'];
    }
}

final class ExactSizeMapShadowsParentRuleMap
{
    public function rules(): array { return ['name' => ['string', 'max:0']]; }
    public function messages(): array
    {
        return ['name' => ['string' => 'Text.', 'max' => 'Fallback max.'], 'name.max' => ['string' => 'String max.']];
    }
}
