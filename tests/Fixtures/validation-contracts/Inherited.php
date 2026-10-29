<?php

namespace ValidationContractFixtures;

abstract class BaseRules
{
    public function rules(): array { return ['name' => ['required']]; }
}

final class MissingInheritedMessage extends BaseRules
{
    public function messages(): array { return []; }
}

trait InheritedMessages
{
    public function messages(): array { return ['name.required' => 'Required.']; }
}

final class ValidInheritedMessage extends BaseRules
{
    use InheritedMessages;
}

trait AliasedRules
{
    public function defineRules(): array { return ['alias' => ['required']]; }
}

final class MissingAliasedMessage
{
    use AliasedRules { defineRules as rules; }
}
