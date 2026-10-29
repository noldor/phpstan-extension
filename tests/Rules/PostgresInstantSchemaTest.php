<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Rules;

use PDO;
use LogicException;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\PostgresConnection;

final class PostgresInstantSchemaTest extends TestCase
{
    public function testAcceptedDeclarationsCompileToMicrosecondTimestampsWithTimeZone(): void
    {
        $connection = new PostgresConnection(static fn (): PDO => throw new LogicException('Unexpected database connection.'));
        $connection->useDefaultSchemaGrammar();
        $table = new Blueprint($connection, 'events');
        $table->create();
        $table->timestampTz('occurred_at', 6);
        $table->dateTimeTz('received_at', 6);
        $table->timestampsTz(6);
        $table->softDeletesTz(precision: 6);

        $this->assertSame([
            'create table "events" ("occurred_at" timestamp(6) with time zone not null, "received_at" timestamp(6) with time zone not null, "created_at" timestamp(6) with time zone null, "updated_at" timestamp(6) with time zone null, "deleted_at" timestamp(6) with time zone null)',
        ], $table->toSql());
    }
}
