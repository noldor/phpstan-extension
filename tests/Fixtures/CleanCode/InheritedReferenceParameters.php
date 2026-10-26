<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

final class InheritedReferenceParameters extends ExternalReferenceParent
{
    public function update(string &$value): void {}

    public function hidden(string &$value): void {}
}

final class InheritedReferenceConstructor extends ExternalReferenceConstructor
{
    public function __construct(string &$value) {}
}
