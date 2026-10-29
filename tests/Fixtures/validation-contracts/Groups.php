<?php

namespace ValidationContractFixtures;

final class WrongGroups
{
    public function rules(): array { return ['title' => ['nullable'], 'ownerId' => ['nullable']]; }
    public function messages(): array { return ['ownerId.required' => 'Required.', 'title.string' => 'Text.']; }
    public function attributes(): array { return ['ownerId' => 'Owner', 'title' => 'Title']; }
}

final class WrongNestedMessages
{
    public function rules(): array { return ['title' => ['nullable'], 'ownerId' => ['nullable']]; }
    public function messages(): array { return ['ownerId.required' => 'Required.', 'title.max' => ['string' => 'Too long.']]; }
}
