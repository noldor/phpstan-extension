<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

interface ExternalReferenceContract
{
    public function transform(string &$value, int $flags = 0): void;
}
