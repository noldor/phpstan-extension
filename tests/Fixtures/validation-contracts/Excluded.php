<?php

namespace ValidationContractFixtures;

final class Excluded
{
    public function rules(): array { return ['name' => ['required']]; }
}
