<?php

declare(strict_types = 1);

namespace AssertFixtures;

use function assert as invariant;

class Writer
{
    public function __construct() {}
    public function save(): bool { return true; }
    public static function write(): string { return 'written'; }
}

final class ChildWriter extends Writer {}

/**
 * @param array<string, bool> $values
 */
function effects(Writer $writer, ChildWriter $child, \PDO $pdo, \PDOStatement $statement, array $values, int $counter, bool $condition): void
{
    $value = false;
    $other = true;
    $knownAssert = 'assert';
    $knownWrite = 'file_put_contents';
    $method = 'save';
    $class = Writer::class;
    $payload = 'contents';

    assert($value = $condition);
    assert($value =& $other);
    assert(($counter += 1) > 0);
    assert(++$counter > 0);
    assert($counter++ > 0);
    assert(--$counter > 0);
    assert($counter-- > 0);
    assert($values['saved'] = $condition);
    assert($writer->save());
    invariant($child->save());
    \ASSERT(Writer::write());
    assert(file_put_contents('/unused', $payload));
    assert($pdo->exec('UPDATE examples SET active = 1'));
    assert($statement->execute());
    assert(assertion: $condition, description: Writer::write());
    assert(description: $value = 'message', assertion: $condition);
    $knownAssert($writer->save());
    assert($knownWrite('/unused', $payload));
    assert($writer->{$method}());
    assert($class::write());
    assert(new Writer());
    assert((static fn (): bool => Writer::write() !== '')());
    assert((static function () use ($writer): bool { return $writer->save(); })());

    assert(static function () use ($writer): void {
        assert($writer->save());
    });
}
