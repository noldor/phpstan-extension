<?php

declare(strict_types = 1);

namespace DatabaseBuilderMocks\PersistenceTests;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Query\Builder;
use DatabaseBuilderMocks\Support\PaymentGateway;

class ConfiguredCalls extends TestCase
{
    public function configuredDoubles(): void
    {
        $this->createConfiguredMock(type: Builder::class, configuration: []);
        self::createConfiguredStub(configuration: [], type: Builder::class);
        self::getStubBuilder(className: Builder::class);
        $this->createConfiguredMock(PaymentGateway::class, []);
        self::createConfiguredStub(PaymentGateway::class, []);
        self::getStubBuilder(PaymentGateway::class);
    }
}
