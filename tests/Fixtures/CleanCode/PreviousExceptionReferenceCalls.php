<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

use RuntimeException;
use Throwable;

final class CauseReplacer
{
    public function replace(Throwable &$error): void
    {
        $error = new RuntimeException('Replacement');
    }

    public function inspect(string $message): void {}
}

final class ValueInspector
{
    public function replace(Throwable $error): void {}
}

function referenceCallsInCatch(callable $operation, callable $dynamic, Throwable $other): void
{
    try { $operation(); } catch (Throwable $error) {
        $replacer = new CauseReplacer();
        $replacer->replace($error);
        throw new RuntimeException('Receiver type was not in catch entry scope');
    }

    try { $operation(); } catch (Throwable $error) {
        replaceAllCauses($other, $error);
        throw new RuntimeException('Trailing variadic argument can be rebound');
    }

    try { $operation(); } catch (Throwable $error) {
        replaceSelectedCause(...['context', &$error]);
        throw new RuntimeException('Unpacked argument can be rebound');
    }

    try { $operation(); } catch (Throwable $error) {
        $dynamic($error);
        throw new RuntimeException('Unknown callback can rebind its argument');
    }

    try { $operation(); } catch (Throwable $error) {
        $replacer = new CauseReplacer();
        $replacer->inspect('No caught exception argument');
        throw new RuntimeException('This still loses the original cause');
    }

    try { $operation(); } catch (Throwable $error) {
        $wrapper = new RuntimeException('Wrapper', previous: $error);
        throw new RuntimeException('Variable chain is outside local alias tracking', previous: $wrapper);
    }

    try { $operation(); } catch (Throwable $error) {
        replaceAllCauses(other: $other, original: $error);
        throw new RuntimeException('Named variadic arguments can be rebound');
    }
}

function replaceAllCauses(Throwable &...$errors): void
{
    foreach ($errors as &$error) {
        $error = new RuntimeException('Replacement');
    }
}

function replaceSelectedCause(string $context, Throwable &$error): void
{
    $error = new RuntimeException('Replacement');
}

function receiverCreatedOnlyInsideCatch(): void
{
    try {
        throw new \LogicException('Original');
    } catch (\LogicException $error) {
        $replacer = new CauseReplacer();
        $replacer->replace($error);
        throw new RuntimeException('No receiver type at catch entry');
    }
}

function receiverReassignedInsideCatch(): void
{
    $replacer = new ValueInspector();

    try {
        throw new \LogicException('Original');
    } catch (\LogicException $error) {
        $replacer = new CauseReplacer();
        $replacer->replace($error);
        throw new RuntimeException('Entry type is stale after receiver reassignment');
    }
}
