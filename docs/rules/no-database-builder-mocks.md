# Database builder test doubles

`NoDatabaseBuilderMocksRule` reports `phpunit.databaseBuilderMock` when a selected
persistence test mocks, stubs or spies on a configured database builder or
relationship type. Exercise application queries against the project's isolated
test database; mock external service contracts at their actual boundary.

The rule is included in the PHP and Laravel sets. Its scope is empty by default.
Configure the persistence tests that own this contract:

```neon
parameters:
    noldors:
        databaseBuilderMocks:
            namespaces:
                - Tests\Integration\Persistence
            classes:
                - Tests\DatabaseTestCase
            excludedClasses:
                - Tests\Integration\Persistence\LegacyAdapterTest
```

Selection follows the shared class scopes: class selectors include subclasses,
namespace selectors include nested namespaces, and exclusions take precedence.
Select actual application persistence tests rather than every test in a project.

The default forbidden types are:

- `Illuminate\Database\Eloquent\Builder`
- `Illuminate\Database\Query\Builder`
- `Illuminate\Database\Eloquent\Relations\Relation`

Their subclasses are included. Missing optional framework classes do not require
installing Laravel in a plain PHP consumer. Replace or extend the types for the
project's installed persistence API:

```neon
parameters:
    noldors:
        databaseBuilderMocks:
            forbiddenTypes:
                - Acme\Persistence\QueryBuilder
```

Use `forbiddenTypes!:` to replace the default list. No class-name suffix or
directory naming convention establishes that a target is a database builder.

```php
// Rejected in the configured persistence-test scope.
$this->createMock(\Illuminate\Database\Query\Builder::class);
$this->partialMock(\Illuminate\Database\Eloquent\Builder::class);
\Mockery::spy(\Illuminate\Database\Eloquent\Relations\HasMany::class);

// Accepted: an application-owned external integration contract.
$gateway = $this->createMock(PaymentGateway::class);
```

## Supported APIs

The rule checks the native implementation of the called method, rather than its
name alone:

- PHPUnit `createMock`, `createStub`, `createPartialMock`, `getMockBuilder`,
  `createConfiguredMock`, `createConfiguredStub` and `getStubBuilder`.
- Mockery `mock` and `spy`, when Mockery is available to PHPStan.
- Laravel's native `InteractsWithContainer::mock`, `partialMock` and `spy` helpers,
  including inherited helpers and trait aliases.

Positional and named target arguments are supported using the installed API's
parameter name. Class aliases, resolved class-name literals and local target
variables work. A bounded class-string type also works when every possible target
is a configured forbidden subtype. A union containing an allowed or unresolved
target remains unchecked. An unrelated method with the same name or an
application override is outside the native API contract.

`getMockBuilder` and `getStubBuilder` are diagnosed where the forbidden target is
selected, regardless of a later builder chain. The rule does not require building
the resulting double to report that selection.

## Analysis limits

This is an argument-sensitive API rule, not whole-program mock tracking.
Unresolved dynamic calls, unpacked target arguments, first-class callables,
arbitrary application wrappers, Mockery's string DSL (`alias:`, `overload:` or
partial-method specifications), and object-proxy targets are outside its initial
coverage. Unknown strings are not guessed from their spelling.

The check does not prove that the alternative test executes the query, uses the
right database engine, checks authorization, or covers locking and transaction
behavior. Those remain integration-test contracts. Eloquent models and database
connections are not forbidden unless explicitly added to `forbiddenTypes`.
