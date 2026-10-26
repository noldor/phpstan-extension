<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

class ExternalReferenceParent
{
    public function update(string &$value): void {}

    private function hidden(string &$value): void {}
}
