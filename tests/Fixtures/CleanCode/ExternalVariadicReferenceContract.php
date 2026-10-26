<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

interface ExternalVariadicReferenceContract
{
    public function update(string &...$values): void;

    public function read(string ...$values): void;
}
