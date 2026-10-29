<?php

namespace ValidationContractFixtures;

final class WrongFields
{
    public function rules(): array
    {
        return [
            'items.sync.*' => ['nullable'],
            'ownerId' => ['nullable'],
            'items' => ['nullable'],
            'tags' => ['nullable'],
            'items.sync' => ['nullable'],
        ];
    }
}

final class OrdinaryAfterRelationship
{
    public function rules(): array { return ['items' => ['nullable'], 'ownerId' => ['nullable']]; }
}

final class ParentAfterChild
{
    public function rules(): array { return ['items.*' => ['nullable'], 'items' => ['nullable']]; }
}

final class SplitRelationship
{
    public function rules(): array { return ['items' => ['nullable'], 'tags' => ['nullable'], 'items.sync' => ['nullable']]; }
}

final class OrdinaryNestedField
{
    public function rules(): array { return ['address.city' => ['nullable'], 'ownerId' => ['nullable']]; }
}
