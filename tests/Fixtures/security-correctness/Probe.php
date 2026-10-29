<?php

namespace Fixtures\SecurityCorrectness;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

function native(\CurlHandle $handle, LoggerInterface $logger): void
{
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    $logger->info('input', ['body' => $_POST]);
    $count = 0;
    assert(++$count > 0);
}

final class PersistenceTest extends TestCase
{
    #[DataProvider('values')]
    public function testValue(int $value): void
    {
        $this->assertSame(1, $value);
    }

    public static function values(): array
    {
        file_get_contents('/nonexistent-fixture-never-executed');
        return [[1]];
    }

    public function testBuilder(): void
    {
        $this->createMock(\Illuminate\Database\Query\Builder::class);
    }
}
