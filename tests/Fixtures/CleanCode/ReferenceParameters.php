<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

function calculate(string $input, string &$output): void
{
    $output = $input;
}

final class ReferenceParameters
{
    public function __construct(int &$count)
    {
        ++$count;
    }

    public function replace(\stdClass &$state): void
    {
        $state = new \stdClass();
    }

    public function normalize(string &...$values): void {}

    public function mutate(\stdClass $state): void
    {
        $state->enabled = true;
    }
}

interface OwnedReferenceContract
{
    public function update(int &$count): void;
}

final class OwnedReferenceImplementation implements OwnedReferenceContract
{
    public function update(int &$count): void
    {
        ++$count;
    }
}

final class ExternalReferenceImplementation implements ExternalReferenceContract
{
    public function transform(string &$value, int $flags = 0): void {}

    public function additional(string &$value): void {}
}

final class ChangedExternalReferenceImplementation implements ExternalReferenceContract
{
    public function transform(string &$value, int &$flags = 0): void {}
}

array_walk([], static function (string &$value): void {});
$callback = static fn (string &$value): string => $value;

function &referenceResult(\stdClass $state): \stdClass
{
    return $state;
}
