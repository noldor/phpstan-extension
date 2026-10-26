<?php

declare(strict_types = 1);

namespace DtoInfrastructure\Contracts;

interface DataTransferObject {}

interface Gateway
{
    public function dispatch(): void;
}

namespace DtoInfrastructure\Infrastructure;

use DtoInfrastructure\Contracts\Gateway;

class Database
{
    public function query(): void {}

    public function escape(string $value): string
    {
        return $value;
    }
}

final class DerivedDatabase extends Database
{
    public function query(): void {}
}

final class HttpGateway implements Gateway
{
    public function dispatch(): void {}
}

final class Network
{
    public static function send(): void {}

    public static function encode(string $value): string
    {
        return $value;
    }
}

final class Filesystem
{
    public static function read(): void {}
}

class Connection
{
    public function __construct() {}
}

final class DerivedConnection extends Connection {}

namespace DtoInfrastructure\Data;

use DtoInfrastructure\Infrastructure\Network;
use DtoInfrastructure\Infrastructure\Database;
use DtoInfrastructure\Infrastructure\Connection;
use DtoInfrastructure\Infrastructure\Filesystem;
use DtoInfrastructure\Infrastructure\HttpGateway;
use DtoInfrastructure\Contracts\DataTransferObject;
use DtoInfrastructure\Infrastructure\DerivedDatabase;
use DtoInfrastructure\Infrastructure\DerivedConnection;

final readonly class Value
{
    public function __construct(
        public string $value,
    ) {}
}

final class MarkedData implements DataTransferObject
{
    public function run(Database $database, DerivedDatabase $derived, HttpGateway $gateway): void
    {
        $database->query();
        $database->query(...);
        $derived->query();
        $gateway->dispatch();
        Network::send();
        Network::send(...);
        Filesystem::read();
        \file_get_contents(__FILE__);
        \FILE_GET_CONTENTS(...);
        new Connection();
        new DerivedConnection();
        new \PDO('sqlite::memory:');

        $database->escape('value');
        Network::encode('value');
        new Value('value');
        \mb_strlen('value');
        \json_encode(['value' => 'value'], \JSON_THROW_ON_ERROR);
    }
}

final class ExplicitData
{
    public function load(Database $database): void
    {
        $database->query();
    }
}

final class UnconfiguredDto
{
    public function load(Database $database): void
    {
        $database->query();
        \file_get_contents(__FILE__);
    }
}

function outsideDataClass(Database $database): void
{
    $database->query();
}

namespace DtoInfrastructure\Scoped\Nested;

use DtoInfrastructure\Infrastructure\Database;

final class Data
{
    public function load(Database $database): void
    {
        $database->query();
    }
}

namespace DtoInfrastructure\ScopedElsewhere;

use DtoInfrastructure\Infrastructure\Database;

final class Data
{
    public function load(Database $database): void
    {
        $database->query();
    }
}
