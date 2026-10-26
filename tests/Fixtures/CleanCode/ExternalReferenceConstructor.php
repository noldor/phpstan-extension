<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

class ExternalReferenceConstructor
{
    public function __construct(string &$value) {}
}
