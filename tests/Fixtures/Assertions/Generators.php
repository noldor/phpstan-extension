<?php

declare(strict_types = 1);

namespace AssertFixtures\Generators;

function generatorExecution(\AssertFixtures\Writer $writer): void
{
    assert((static function () use ($writer): \Generator {
        yield $writer->save();
    })());

    assert((static fn (): \Generator => yield $writer->save())());

    assert((static function (bool $saved) use ($writer): \Generator {
        yield $writer->save();
    })($writer->save()));

    assert((static fn (bool $saved): \Generator => yield $writer->save())($writer->save()));

    assert((static function () use ($writer): bool {
        (static function () use ($writer): \Generator {
            yield $writer->save();
        })();

        return $writer->save();
    })());

    assert((static fn (): bool => $writer->save())());
}
