<?php

namespace DependencyAccess;

final class DateAndUrlService
{
    public function run(): void
    {
        \now();
        \today();
        \uri('profile.show');
        \collect([]);
        \data_get([], 'key');
        \blank('');
        \value(static fn () => 1);
    }
}
