# MutableCollectionExposureRule

Included in the [PHP set](../README.md). Reports
`encapsulation.mutableCollectionExposure` when a public or protected method of a
configured invariant owner returns one of its stored mutable collections.

The rule is inactive until owners or owner namespaces are configured. Select
classes that protect invariants over their collections; data transfer objects,
framework models and other data-oriented APIs need not have this contract.

## Configuration

```neon
parameters:
    noldors:
        mutableCollections:
            owners:
                - App\Domain\AggregateRoot
                - App\Domain\ProtectsCollectionInvariants
            namespaces:
                - App\Domain\Orders
```

`owners` selects the named classes, descendants and interface implementations.
`namespaces` selects classes within the namespace and its nested namespaces,
matching complete namespace segments. Both lists are empty by default.

The default `types` list contains `Illuminate\Support\Collection` and
`Doctrine\Common\Collections\Collection`; their subtypes are included. Neither
package becomes a runtime dependency. To add a custom mutable collection:

```neon
parameters:
    noldors:
        mutableCollections:
            types:
                - App\Support\MutableBag
```

NEON merges this list with the defaults. Use `types!` instead of `types` to
replace the list, for example when a project uses a different collection API.

## Example

For a configured owner, this exposes the collection object held by the owner:

```php
final class Order
{
    public function __construct(private readonly Collection $items) {}

    public function items(): Collection
    {
        return $this->items;
    }
}
```

The caller could mutate the collection without using the owner's invariant
checks. `readonly` prevents reassigning the property; it does not freeze the
collection. Return a suitable snapshot or a read-only view according to the
domain contract, for example:

```php
public function items(): array
{
    return $this->items->all();
}
```

Copying the collection or returning an array does not copy mutable elements.
Choose element exposure and copying semantics for the owner's actual invariants.

## Detection boundaries

- Direct `return $this->items` is checked wherever it occurs in the method,
  including branches. Nullable and union types containing a configured mutable
  collection are covered when PHPStan can identify that type.
- Straight-line local aliases are covered: `$items = $this->items;` followed by
  `return $items;`, including chains of plain alias assignments. Other statements
  invalidate alias tracking. Aliases across branches, loops, calls or references
  are outside this check.
- Private methods, arrays, `clone` expressions, new collections, and unrelated
  iterator types are accepted. This does not certify that every iterator or
  snapshot is deeply immutable.
- Property getter hooks, magic properties, dynamic property names, indirect
  getter calls, nested return structures and closure bodies are outside this
  check. Such constructs do not establish that the returned value is the
  stored collection object.
- Broad types such as `mixed`, `object` or `Traversable` cannot establish a
  configured mutable collection type on their own. Keep useful property types.
