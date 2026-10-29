<?php

namespace ValidationContractFixtures;

final class Unknown
{
    public function rules(): array
    {
        if (random_int(0, 1)) { return ['name' => ['required']]; }
        return ['email' => ['required', 'email']];
    }
}

final class UnknownMessages
{
    public function rules(): array { return ['name' => ['required']]; }
    public function messages(): array { return $this->catalog(); }
    private function catalog(): array { return ['name.required' => 'Required.']; }
}

function clearRules(array &$rules): bool { $rules = []; return true; }

final class UnknownMutation
{
    public function rules(): array
    {
        $rules = ['name' => ['required']];
        $changed = clearRules($rules);
        return $rules;
    }
}

final class NumericFieldKeys
{
    public function rules(): array { return ['0' => ['required']]; }
}
