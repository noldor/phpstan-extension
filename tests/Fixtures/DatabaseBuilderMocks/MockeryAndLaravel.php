<?php

declare(strict_types = 1);

namespace DatabaseBuilderMocks\PersistenceTests;

use Mockery as Doubles;
use Illuminate\Database\Query\Builder;
use DatabaseBuilderMocks\Support\CustomBuilder;
use DatabaseBuilderMocks\Support\PaymentGateway;
use DatabaseBuilderMocks\Support\LaravelTestCase;
use DatabaseBuilderMocks\Support\UnrelatedMockFactory;
use DatabaseBuilderMocks\Support\OverriddenLaravelTestCase;

class MockeryCalls
{
    public function mocks(bool $first, string $unknown): void
    {
        Doubles::mock(Builder::class);
        \Mockery::spy(CustomBuilder::class);
        Doubles::mock('Illuminate\\Database\\Query\\Builder');
        $target = Builder::class;
        Doubles::mock($target);
        Doubles::mock($first ? Builder::class : CustomBuilder::class);
        Doubles::mock(PaymentGateway::class);
        Doubles::spy($first ? Builder::class : PaymentGateway::class);
        Doubles::mock($unknown);
        UnrelatedMockFactory::spy(Builder::class);
        $callable = Doubles::mock(...);
    }
}

class LaravelCalls extends LaravelTestCase
{
    public function helpers(UnrelatedMockFactory $factory): void
    {
        $this->mock(Builder::class);
        $this->partialMock(abstract: CustomBuilder::class);
        $this->spy(Builder::class);
        $this->aliasedMock(Builder::class);
        $this->mock(abstract: PaymentGateway::class);
        $this->partialMock(PaymentGateway::class);
        $this->spy(PaymentGateway::class);
        $factory->mock(Builder::class);
    }
}

class OverrideCalls extends OverriddenLaravelTestCase
{
    public function ownMethod(): void
    {
        $this->mock(Builder::class);
    }
}
