# PHPUnit data-provider side effects

`PHPUnitDataProviderSideEffectsRule` reports `phpunit.providerSideEffect` for
configured direct database, I/O, process and mock-creation calls executed while a
registered PHPUnit data provider produces its data. It is included in the PHP
set; the Laravel set adds its database and persisting factory APIs.

PHPUnit discovers providers before test setup. Return data or deferred fixture
factories and perform the effect after setup:

```php
#[DataProvider('cases')]
public function testRecord(Closure $makeRecord): void
{
    $record = $makeRecord();
    // Exercise the record after the application's test setup.
}

public static function cases(): array
{
    return [[static fn () => Record::factory()->create()]];
}
```

The eager version is rejected:

```php
public static function cases(): array
{
    return [[Record::factory()->create()]];
}
```

## Provider discovery

The check reads actual `PHPUnit\Framework\Attributes\DataProvider` and
`DataProviderExternal` metadata on public methods of PHPUnit `TestCase`
subclasses. It resolves known public static provider methods, including inherited
methods, external provider classes and providers overridden by a test subclass.
Repeated references produce one diagnostic per offending call. No provider name,
method suffix or application namespace convention is inferred.

Include both the test classes referencing providers and their provider
implementations in PHPStan's analysis paths. Scanning a provider for symbols
alone does not run its effect collector. The collectors use PHPStan's cross-file
analysis and result cache; they do not execute providers or attributes.

Literal scalar/array data, immutable value objects and callable references remain
valid. Deferred closure/arrow-function bodies are not provider execution. Direct
immediately invoked non-generator closures are checked. Calling a generator
closure or arrow function creates a generator without executing its body; those
bodies remain deferred. Invocation arguments are still evaluated eagerly and are
checked in both cases. A registered provider method that itself yields datasets
is checked because PHPUnit iterates that provider during discovery. Calls in
unrelated methods and unreferenced methods named like providers are not restricted
by this rule.

## Effect selectors

The framework-independent defaults cover configured PDO/PDOStatement operations,
known file/process/network functions and PHPUnit's mock/stub creation APIs. The
Laravel additions cover known database execution, delivery and persisting factory
APIs. A nonpersisting factory `make()` is not a database write; this check does not
prove deep immutability of every returned object.

Add the consumer's own direct APIs with the same selector shape:

```neon
parameters:
    noldors:
        phpunitDataProviders:
            forbiddenMethods:
                - {class: App\Infrastructure\FixtureWriter, methods: [record]}
            forbiddenFunctions:
                - App\Infrastructure\load_fixture
```

Selectors match resolved method declaring classes, descendants/implementations
and selected methods on trait-bearing classes. `'*'` selects every method of the
chosen API, including its constructor when resolved. Function names are resolved
fully qualified names. Matching is case-insensitive; a leading namespace
separator is optional. Use `forbiddenMethods!:` or `forbiddenFunctions!:` to
replace the corresponding inherited defaults.

## Limits

This is finite direct-call detection, not whole-program effect or immutability
analysis. Unknown metadata arguments, unresolved dynamic calls, arbitrary helper
bodies, constructors outside selected APIs and custom provider mechanisms remain
outside the check. It does not trace a closure assigned to a variable and invoked
later, subsequent iteration of a generator produced by an anonymous function,
callbacks passed into arbitrary executing functions, legacy PHPDoc providers or
`DataProviderClosure`. Deferred code can still be effectful when the test
deliberately executes it after setup.

The check does not validate provider signatures, returned dataset shapes or
whether the test is discoverable. PHPUnit retains those contracts. Real test
execution remains required.
