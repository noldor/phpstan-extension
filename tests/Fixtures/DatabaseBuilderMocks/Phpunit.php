<?php

declare(strict_types = 1);

namespace DatabaseBuilderMocks\PersistenceTests;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use DatabaseBuilderMocks\Support\CustomBuilder;
use DatabaseBuilderMocks\Support\PaymentGateway;
use DatabaseBuilderMocks\Support\UnrelatedMockFactory;
use Illuminate\Database\Eloquent\Builder as ModelQuery;

class PhpunitCalls extends TestCase
{
    public function rejected(bool $first, CustomBuilder $builder): void
    {
        $this->createMock(Builder::class);
        $this->createMock(type: ModelQuery::class);
        self::createStub(Builder::class);
        $this->createPartialMock(methods: ['getResults'], type: HasMany::class);
        $this->getMockBuilder(className: CustomBuilder::class)->getMock();
        $this->createMock('Illuminate\\Database\\Query\\Builder');
        $target = CustomBuilder::class;
        $this->createMock($target);
        $this->createMock($first ? Builder::class : CustomBuilder::class);
        $this->createMock($builder::class);
        static::createStub(type: CustomBuilder::class);
    }

    /** @param class-string<Builder> $target */
    public function knownBuilder(string $target): void
    {
        $this->createStub($target);
    }

    public function accepted(bool $first, string $unknown, UnrelatedMockFactory $factory): void
    {
        $this->createMock(PaymentGateway::class);
        self::createStub(type: PaymentGateway::class);
        $this->createPartialMock(PaymentGateway::class, []);
        $this->getMockBuilder(PaymentGateway::class);
        $this->createMock($first ? Builder::class : PaymentGateway::class);
        $this->createMock($unknown);
        $factory->createMock(Builder::class);
        $callable = $this->createMock(...);
        $this->createMock(...[Builder::class]);
    }
}

class InheritedCalls extends PhpunitCalls
{
    public function inheritedMockApi(): void
    {
        $this->createMock(CustomBuilder::class);
    }
}

namespace DatabaseBuilderMocks\OutsideTests;

class UnselectedCalls extends \PHPUnit\Framework\TestCase
{
    public function unselectedMock(): void
    {
        $this->createMock(\Illuminate\Database\Query\Builder::class);
    }
}
