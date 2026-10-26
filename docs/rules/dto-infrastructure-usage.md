# DTO infrastructure usage

Included in the [PHP and Laravel sets](../README.md). Reports
`architecture.dtoInfrastructureUsage` when an explicitly selected DTO directly
uses a configured infrastructure method or function. The extension uses
PHPStan's [restricted usage APIs](https://phpstan.org/developing-extensions/restricted-usage-extensions).

## Select DTO classes

The default DTO scope is empty, so connecting a set does not infer DTO roles or
report these errors until the consumer configures its own contract:

```neon
parameters:
    noldors:
        dto:
            classes:
                - App\Contracts\DataTransferObject
            namespaces:
                - App\Data
```

`classes` accepts concrete classes, base classes and interfaces. Subclasses and
implementations are included. `namespaces` includes classes in the exact namespace
and its nested namespaces; `App\DataHelpers` does not match `App\Data`. The two
lists are alternatives: a class only needs to match one of them. An unconfigured
class is allowed even when its name ends in `Dto`. Global functions are outside
the DTO scope.

## Configure infrastructure APIs

The sets supply infrastructure selectors. Extend these with application APIs:

```neon
parameters:
    noldors:
        dto:
            forbiddenMethods:
                -
                    class: App\Infrastructure\InventoryClient
                    methods: [fetch, reserve]
                -
                    class: App\Infrastructure\AuditWriter
                    methods: ['*']
            forbiddenFunctions:
                - App\Infrastructure\dispatch_job
```

Each method selector contains an exact class or interface name and a list of
method names. It applies to the matching declaring class and its descendants or
implementations. Selectors are case-insensitive; a leading namespace separator is
optional. A method name of `'*'` selects every method, including `__construct`.
Select individual methods when a class also provides legitimate local
transformations. Function selectors are exact fully qualified names, without
wildcards.

NEON merges inherited lists. To replace the set's selectors, use
`forbiddenMethods!:` or `forbiddenFunctions!:` instead of extending them. Likewise,
`classes!: []` and `namespaces!: []` clear an inherited DTO scope.

## What it checks

- Instance and static method calls to selected APIs, including inherited and
  overridden implementations.
- First-class method and function callables, because these hand access to an
  infrastructure operation to another caller.
- Construction when `__construct` or `'*'` is selected for that infrastructure
  class. For example, selecting all PDO methods also rejects `new PDO($dsn)`.
- Reads as well as writes when their API is selected. A database read is still
  infrastructure work.

For example, a DTO should receive an already loaded value instead of reading a
file itself:

```php
final readonly class DocumentData implements DataTransferObject
{
    public function __construct(public string $contents) {}

    public static function fromPath(string $path): self
    {
        return new self(file_get_contents($path));
    }
}
```

With the DTO interface and `file_get_contents` selected, the file read is
rejected. Load the file in the calling operation and pass the contents to the
constructor.

Creating ordinary values, converting supplied values, serialization and local
helpers remain allowed unless their APIs are explicitly selected. Type hints and
mere class references are not prohibited.

## Limits

This is a direct API restriction, not transitive effect analysis. It does not
inspect an arbitrary helper's body to discover hidden HTTP requests, database
access or other effects. Add the helper's API to the selectors when appropriate.
Dynamic callables that PHPStan cannot resolve are also outside this check.

Method matching uses the method's declaring class. A restriction on a child
class does not generally restrict a method it inherits from an unselected parent;
select the parent API when the restriction belongs to that shared contract.

PHPStan itself rejects constructor callables such as `new PDO(...)` with
`callable.notSupported`; ordinary `new PDO($dsn)` construction is supported by
this extension. No claim is made that all business logic can be identified or
excluded automatically.
