<?php

declare(strict_types = 1);

namespace DatabaseBuilderMocks\Support;

use Illuminate\Database\Query\Builder;

interface PaymentGateway {}

class CustomBuilder extends Builder {}

final class UnrelatedMockFactory
{
    public function createMock(string $type): object
    {
        return new \stdClass();
    }

    public function mock(string $abstract): object
    {
        return new \stdClass();
    }

    public static function spy(string $abstract): object
    {
        return new \stdClass();
    }
}

abstract class LaravelTestCase extends \PHPUnit\Framework\TestCase
{
    use \Illuminate\Foundation\Testing\Concerns\InteractsWithContainer {
        mock as aliasedMock;
    }
}

abstract class OverriddenLaravelTestCase extends LaravelTestCase
{
    protected function mock($abstract, ?\Closure $mock = null): object
    {
        return new \stdClass();
    }
}
