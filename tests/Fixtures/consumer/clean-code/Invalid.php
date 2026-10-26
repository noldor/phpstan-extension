<?php

namespace Noldors\PHPStan\Tests\Fixtures\CleanCodeConsumer;

function rewrite(string &$value): void
{
    $value = 'changed';
}

function translate(): void
{
    try {
        throw new \LogicException('Original failure.');
    } catch (\LogicException $exception) {
        throw new \RuntimeException('Translated failure.');
    }
}
