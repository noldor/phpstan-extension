<?php

declare(strict_types = 1);

namespace AssertFixtures\DynamicOuter;

function ordinary(mixed ...$arguments): bool
{
    return true;
}

function dynamic(\AssertFixtures\Writer $writer): void
{
    $callback = __NAMESPACE__ . '\\ordinary';
    $callback($callback = 'assert', $writer->save());

    $assertion = 'assert';
    $assertion($writer->save());
}
