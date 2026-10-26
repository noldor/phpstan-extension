<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\MutableCollections;

use ArrayIterator;
use Closure;
use Illuminate\Support\Collection;
use Traversable;

interface InvariantOwner {}

final class MutableBag {}

class Basket implements InvariantOwner
{
    public function __construct(
        private readonly Collection $items,
        private MutableBag $bag,
        private array $labels,
        private Collection|null $optionalItems,
    ) {}

    public function items(): Collection
    {
        return $this->items;
    }

    protected function protectedItems(): Collection
    {
        return $this->items;
    }

    public function aliasedItems(): Collection
    {
        $items = $this->items;
        $alias = $items;
        return $alias;
    }

    public function optionalItems(bool $include): Collection|null
    {
        if ($include) {
            return $this->optionalItems;
        }

        return null;
    }

    public function labels(): array
    {
        return $this->labels;
    }

    public function copiedItems(): Collection
    {
        return clone $this->items;
    }

    public function freshItems(): Collection
    {
        return new Collection($this->items);
    }

    public function copiedAlias(): Collection
    {
        $items = $this->items;
        $items = clone $items;
        return $items;
    }

    public function iterator(): Traversable
    {
        return new ArrayIterator($this->labels);
    }

    public function deferredRead(): Closure
    {
        return fn (): Collection => $this->items;
    }

    public function externalItems(Collection $items): Collection
    {
        return $items;
    }

    public function reassignedAlias(bool $replace): Collection
    {
        $items = $this->items;
        if ($replace) {
            $items = new Collection();
        }

        return $items;
    }

    public function returnedByReferenceCallback(Closure $replace): Collection
    {
        $items = $this->items;
        $replace($items);
        return $items;
    }

    private function internalItems(): Collection
    {
        return $this->items;
    }
}

final class SpecializedBasket extends Basket
{
    public function bag(MutableBag $bag): MutableBag
    {
        return $this->customBag;
    }

    private MutableBag $customBag;
}

final class BasketData
{
    public function __construct(private Collection $items) {}

    public function items(): Collection
    {
        return $this->items;
    }
}

final class IteratorOwner implements InvariantOwner
{
    public function __construct(private ArrayIterator $items) {}

    public function items(): ArrayIterator
    {
        return $this->items;
    }
}

final class HookOwner implements InvariantOwner
{
    private Collection $items {
        get => clone $this->storedItems;
    }

    public function __construct(private Collection $storedItems) {}

    public function items(): Collection
    {
        return $this->items;
    }
}
