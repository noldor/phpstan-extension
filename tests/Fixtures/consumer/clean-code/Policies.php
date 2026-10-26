<?php

namespace Noldors\PHPStan\Tests\Fixtures\CleanCodeConsumer;

final class MutableBag
{
    public function clear(): void {}
}

final class Order
{
    public function __construct(private readonly MutableBag $items) {}

    public function items(): MutableBag
    {
        return $this->items;
    }
}

final readonly class DocumentData
{
    public function __construct(public string $contents) {}

    public static function fromPath(string $path): self
    {
        return new self(\file_get_contents($path));
    }
}

final readonly class LaravelData
{
    public function __construct(private \Illuminate\Contracts\Filesystem\Filesystem $files) {}

    public function contents(): string
    {
        return $this->files->get('document.txt');
    }
}
