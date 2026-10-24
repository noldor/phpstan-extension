<?php

declare(strict_types = 1);

namespace ValidationFixtures;

use RuntimeException;

final class StaticRuleMethods
{
    public function run(string $endpoint): void
    {
        if (! WebPushEndpoint::allowed($endpoint)) {
            throw new RuntimeException('Web Push endpoint is not allowed.');
        }

        WebPushEndpoint::allowed(...);
        WebPushEndpoint::create();
        WebPushEndpoint::create(...);
        new WebPushEndpoint();
        \Illuminate\Validation\Rule::required();
        \Illuminate\Validation\Rule::forEach(fn () => []);
        \Illuminate\Validation\Rule::required(...);
        \Illuminate\Validation\Rule::forEach(...);
        \Illuminate\Validation\Rules\Password::defaults();
        \Illuminate\Validation\Rules\Password::defaults(...);
        CustomPassword::defaults();
        CustomPassword::defaults(...);
        CustomPassword::allowed($endpoint);
        CustomPassword::allowed(...);
    }
}
