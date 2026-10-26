<?php

namespace Noldors\PHPStan\Tests\Fixtures\CauseRebinding;

final class CauseReplacer
{
    public function replace(\Throwable &$error): void
    {
        $error = new \LogicException('Replacement.');
    }
}

final class ValueInspector
{
    public function replace(\Throwable $error): void {}
}

function rebindCaughtException(): void
{
    try {
        throw new \LogicException('Original.');
    } catch (\LogicException $error) {
        $replacer = new CauseReplacer();
        $replacer->replace($error);
        throw new \RuntimeException('Independent failure.');
    }
}

function rebindAnExistingReceiver(): void
{
    $replacer = new ValueInspector();

    try {
        throw new \LogicException('Original.');
    } catch (\LogicException $error) {
        $replacer = new CauseReplacer();
        $replacer->replace($error);
        throw new \RuntimeException('Independent failure.');
    }
}
