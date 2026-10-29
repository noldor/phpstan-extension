<?php

declare(strict_types=1);

namespace ProviderFixtures;

class ExternalProviderBase
{
    public static function records(): array
    {
        return [[\file_get_contents('/not-executed/external.txt')]]; // @expect phpunit.providerSideEffect
    }

    public static function unreferenced(): array
    {
        return [[\file_get_contents('/not-executed/unreferenced.txt')]];
    }
}

final class ExternalProviders extends ExternalProviderBase {}
