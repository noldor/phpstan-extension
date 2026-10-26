<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

final class ExpandedReferenceParameters implements ExternalVariadicReferenceContract
{
    public function update(string &$first = '', string &...$rest): void {}

    public function read(string &...$values): void {}
}
