<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use Illuminate\Validation\Rules\Date;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Rules\Password;

final class RuleBuilders
{
    public function run(Unique $unique, Date $date, Password $password): void
    {
        $unique->where('office_id', 7);
        $unique->ignore(42);
        $date->after('today');
        $password->mixedCase();
        $unique->where(...);
        $unique->queryCallbacks();
        $unique->__toString();
        $password->passes('password', 'example');
        $password->message();
    }
}
