<?php

declare(strict_types = 1);

namespace AssertFixtures\Accepted;

use function assert as invariant;

final class Value
{
    public function __construct(public readonly bool $valid) {}
    public function isValid(): bool { return $this->valid; }
    public function save(): bool { return true; }
}

/**
 * @param callable(): bool $callback
 */
function accepted(Value $value, callable $callback, string $unknownFunction, string $unknownMethod, bool $condition, \AssertFixtures\Writer|Value $receiver): void
{
    assert($condition);
    invariant($value->isValid());
    assert(new Value($condition));
    assert($value->save());
    assert(static fn (): bool => file_put_contents('/unused', 'contents') > 0);
    assert(static function (): bool { return file_put_contents('/unused', 'contents') > 0; });
    assert(new class { public function save(): bool { return file_put_contents('/unused', 'contents') > 0; } });
    assert($callback());
    assert($unknownFunction());
    assert($value->{$unknownMethod}());
    assert(file_put_contents(...));
    assert($value->save(...));
    assert(\AssertFixtures\Writer::write(...));
    assert($receiver instanceof Value && $receiver->save());
    $native = assert(...);
    $closure = static function () use ($value): void { $value->save(); };
}

namespace AssertFixtures\Shadow;

use function AssertFixtures\Shadow\customAssert as assert;

function customAssert(mixed $value): bool { return true; }

function shadow(bool $condition): void
{
    assert($local = $condition);
}
