<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

use RuntimeException;
use Throwable;

final class ReorderedException extends RuntimeException
{
    public function __construct(?Throwable $previous = null, string $message = '')
    {
        parent::__construct($message, 0, $previous);
    }
}

final class ContextException extends RuntimeException
{
    public function __construct(mixed $context, ?Throwable $previous = null)
    {
        parent::__construct('Failed', 0, $previous);
    }
}

final class MessageOnlyException extends RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}

function previousExceptions(callable $operation): void
{
    try { $operation(); } catch (Throwable $error) {
        throw new RuntimeException('Lost cause');
    }

    try { $operation(); } catch (Throwable $error) {
        throw new RuntimeException($error->getMessage());
    }

    try { $operation(); } catch (Throwable $error) {
        throw new RuntimeException('Kept', 0, $error);
    }

    try { $operation(); } catch (Throwable $error) {
        throw new RuntimeException(previous: $error);
    }

    try { $operation(); } catch (Throwable $error) {
        throw new ReorderedException($error, 'Kept');
    }

    try { $operation(); } catch (Throwable $error) {
        throw new ReorderedException(message: 'Lost');
    }

    try { $operation(); } catch (Throwable $error) {
        throw new ContextException($error);
    }

    try { $operation(); } catch (Throwable $error) {
        throw new ContextException(context: [], previous: $error);
    }

    try { $operation(); } catch (Throwable $error) {
        throw $error;
    }

    try { $operation(); } catch (Throwable $error) {
        $cause = $error;
        throw new RuntimeException('Kept alias', previous: $cause);
    }

    try { $operation(); } catch (Throwable $error) {
        throw new MessageOnlyException('No supported previous parameter');
    }

    try { $operation(); } catch (Throwable $error) {
        $callback = static function (): void {
            throw new RuntimeException('Unrelated deferred exception');
        };
    }

    try { $operation(); } catch (Throwable $error) {
        try {
            throw new RuntimeException('Unrelated operation');
        } catch (\LogicException $inner) {
            throw new RuntimeException('Inner translation loses cause');
        }
    }

    try { $operation(); } catch (Throwable $error) {
        $error = new RuntimeException('Replacement');
        throw new RuntimeException('Unknown relationship after reassignment');
    }

    try { $operation(); } catch (Throwable $error) {
        if ($operation()) {
            throw new RuntimeException('Lost in branch');
        }
    }
}
