<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

use RuntimeException;
use Throwable;

final class CauseException extends RuntimeException
{
    public function __construct(string $message = '', ?Throwable $cause = null)
    {
        parent::__construct($message, 0, $cause);
    }
}

function customCauseParameters(callable $operation): void
{
    try { $operation(); } catch (Throwable $error) {
        throw new CauseException('Lost');
    }

    try { $operation(); } catch (Throwable $error) {
        throw new CauseException(cause: $error);
    }

    try { $operation(); } catch (Throwable $error) {
        throw new RuntimeException(previous: new RuntimeException('Wrapper', previous: $error));
    }

    try { $operation(); } catch (Throwable $error) {
        throw new RuntimeException(previous: new RuntimeException('Wrapper loses cause'));
    }

    try { $operation(); } catch (Throwable $error) {
        throw new RuntimeException(previous: wrapCause($error));
    }

    try { $operation(); } catch (Throwable $error) {
        try { $operation(); } catch (\LogicException $inner) {}
        throw new RuntimeException('Sibling still loses cause');
    }
}

function reassignedCauses(callable $operation, Throwable $replacement): void
{
    try { $operation(); } catch (Throwable $error) {
        unset($error);
        throw new RuntimeException('No known current caught variable');
    }

    try { $operation(); } catch (Throwable $error) {
        [$error] = [$replacement];
        throw new RuntimeException('Destructuring replaces caught variable');
    }

    try { $operation(); } catch (Throwable $error) {
        $replace = static function () use (&$error, $replacement): void {
            $error = $replacement;
        };
        $replace();
        throw new RuntimeException('Reference capture replaces caught variable');
    }

    try { $operation(); } catch (Throwable $error) {
        replaceCaughtException($error, $replacement);
        throw new RuntimeException('Reference call replaces caught variable');
    }
}

function replaceCaughtException(Throwable &$error, Throwable $replacement): void
{
    $error = $replacement;
}

function wrapCause(Throwable $cause): Throwable
{
    return new RuntimeException(previous: $cause);
}
