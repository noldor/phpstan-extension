<?php

namespace ValidationContractFixtures;

use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\ValidationRule;

final class OwnedMessage implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void { $fail('Public failure.'); }
}

final class Valid
{
    private const string TITLE = 'title';

    public function rules(): array
    {
        $ordinary = [self::TITLE => 'required|string', 'ownerId' => ['bool']];
        $nested = ['items' => ['array'], 'items.*' => ['bail', 'required', 'integer', new OwnedMessage()]];
        return [...$ordinary, ...$nested];
    }
    public function messages(): array
    {
        $first = ['title.required' => __('validation.required'), 'title.string' => 'Text.'];
        return $first + ['ownerId.boolean' => 'Boolean.', 'items.array' => 'List.', 'items.*.required' => 'Required.', 'items.*.integer' => 'Integer.'];
    }
    public function attributes(): array { return ['title' => 'Title', 'ownerId' => 'Owner', 'items' => 'Items', 'items.*' => 'Item']; }
}

final class ConditionalBuilder
{
    public function rules(): array { return ['value' => [Rule::requiredIf(false), Rule::prohibitedIf(false)]]; }
}

final class NestedMessages
{
    public function rules(): array { return ['items' => ['array'], 'items.*' => ['required', 'integer']]; }
    public function messages(): array { return ['items.array' => 'List.', 'items.*' => ['required' => 'Required.', 'integer' => 'Integer.']]; }
}

final class RegexMessages
{
    public function rules(): array { return ['title' => ['regex:/foo|integer|bar/']]; }
    public function messages(): array { return ['title.regex' => 'Use the expected format.']; }
}

abstract class ConstantBase
{
    private const array FIRST = ['title' => ['nullable']];
    private const array RULES = self::FIRST + ['ownerId' => ['nullable']];

    public function rules(): array { return self::RULES; }
}

final class ConstantComposition extends ConstantBase
{
    public function messages(): array { return ['title.required' => 'Required.', 'ownerId.integer' => 'Integer.']; }
}

final class CanonicalRuleNames
{
    public function rules(): array { return ['title' => ['Required', 'NotRegex:/forbidden/']]; }
    public function messages(): array { return ['title.required' => 'Required.', 'title.not_regex' => 'Use another value.']; }
}
