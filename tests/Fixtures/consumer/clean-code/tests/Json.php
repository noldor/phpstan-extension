<?php

namespace Noldors\PHPStan\Tests\Fixtures\CleanCodeConsumer\Tests;

function jsonCalls(mixed $value): void
{
    \json_encode($value);
    \json_decode('{"ok":true}');
    \json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    \json_decode('{"ok":true}', associative: true, flags: JSON_THROW_ON_ERROR);
}
