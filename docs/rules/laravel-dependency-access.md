# Dependency injection and transport boundaries

These opt-in checks belong to the Laravel set. Select application use cases and
classes whose framework lifecycle supports dependency injection. Consumer
namespaces, class names and directory roots are configuration, not assumptions
inside the checks.

```neon
includes:
    - vendor/noldors/phpstan-extension/src/laravel.neon

parameters:
    noldors:
        laravel:
            useCases:
                namespaces: [Company\Application\UseCases]
            dependencyInjection:
                namespaces:
                    - Company\Application\Services
                    - Company\Application\UseCases
                    - Company\Application\Jobs
```

## U1: keep transport types outside use cases

`architecture.useCaseTransport` rejects references to configured HTTP/GraphQL
transport types in selected use cases. It uses PHPStan's restricted class-name
extension, covering native parameter, property and return types, constructors,
class references and PHPDoc types that PHPStan checks at the selected analysis
level. Subclasses of configured types are included. Promoted properties can
produce separate parameter and property diagnostics with their own descriptions.

Pass operation data, domain values and an explicit actor or guard instead of a
request or GraphQL execution context. Configure additional transport types when
the consumer has wrappers or another transport adapter:

```neon
parameters:
    noldors:
        laravel:
            useCaseTransportTypes:
                - Company\Http\RequestEnvelope
                - Company\GraphQL\OperationArguments
```

The defaults cover Symfony's HTTP request base (including Laravel requests),
GraphQL ResolveInfo and Lighthouse ArgumentSet/ResolveInfo types when installed.
An uninstalled optional transport integration is not required by this package.
The check follows class references that PHPStan analyses; it does not discover
transport access hidden behind untyped values or arbitrary helper bodies.

## U2: pass the actor explicitly

`architecture.useCaseActor` rejects direct global actor lookup in selected use
cases: `Auth::user()`, `Auth::id()`, `auth()->user()`, `auth('api')->id()` and
lookups through global facade guard/driver chains. Imported facade aliases,
known dynamic names, class strings, nullsafe calls and first-class method
callables are covered. An injected `Guard` or actor remains accepted.

```php
// Rejected inside a selected use case.
$actor = Auth::guard('api')->user();

// Accepted: the operation's caller supplies the actor or guard.
public function handle(Guard $guard): void
{
    $actor = $guard->user();
}
```

`noldors.laravel.globalActorMethods` defaults to `[user, id]` and can include
additional actor lookup methods. Other auth operations are not automatically
actor lookup. The rule recognizes direct expression chains; it does not trace
an arbitrary helper or a separately stored factory/guard back to global state.
The dependency-injection check below independently forbids the global acquisition
inside selected DI classes.

## Require dependency injection

`architecture.dependencyInjection` rejects these mechanisms inside the selected
`dependencyInjection.classes` or `dependencyInjection.namespaces`:

- Laravel facade class references, identified by inheritance from
  `Illuminate\Support\Facades\Facade`, including custom facades, imported aliases,
  dynamic method names and known class strings. Larastan method forwarding does
  not hide the original facade source.
- Global Laravel helper calls and first-class function callables. The default
  `allLaravelHelpers: true` includes utility helpers such as `collect`, `data_get`,
  `blank` and `value`. Native PHP functions and user-defined namespaced functions
  with the same short names remain accepted.
- Service locator retrieval through configured container types and method names,
  including injected containers, PSR containers, static `getInstance()`, nullsafe
  calls and first-class method callables. Container array reads are also checked;
  plain PHP array reads and container offset assignments are not retrieval.

The defaults exclude Eloquent models, migrations, service providers and PHPUnit
TestCase subclasses. The standard `tests` and `database/migrations` directories
are excluded as well. This preserves framework integration points that do not
use the application's DI lifecycle. Additional exclusions are explicit:

```neon
parameters:
    noldors:
        laravel:
            dependencyInjection:
                excludedClasses:
                    - Company\Infrastructure\ExternalLifecycleAdapter
                excludedNamespaces:
                    - Company\Generated
                excludedPaths:
                    - custom-tests
```

The class and namespace selectors support inheritance and namespace boundaries.
Exclusions take precedence. Paths are files or directories relative to PHPStan's
working directory, or absolute paths. The defaults do not select any application
class until classes or namespaces are configured. Facade definitions or other
technical adapters included by an unusually broad namespace selection can be
excluded explicitly.

For a narrower helper policy, set `allLaravelHelpers: false`. This retains the
framework/service helper set in `LaravelHelperFunctions::SERVICES` and accepts
additional utility helpers. `helpers` adds explicitly named helper APIs in either
mode. All-helper mode recognizes global functions by their Laravel helper source
path and uses a known Laravel helper-name catalog for reflected signatures whose
original source is unavailable. Recognition reads reflection metadata only; it
does not load application code or scan/execute arbitrary filesystem files.

The locator defaults use `Illuminate\Contracts\Container\Container`,
`Illuminate\Container\Container` and `Psr\Container\ContainerInterface`, with
`[make, makeWith, get, offsetGet, getInstance, call, build, factory]` methods.
Consumers can add their own locator contracts or method names:

```neon
parameters:
    noldors:
        laravel:
            dependencyInjection:
                serviceLocatorTypes: [Company\Infrastructure\ServiceRegistry]
                serviceLocatorMethods: [fetch]
                helpers: [service_registry]
```

These are diagnostics. The rules do not invent constructor parameters, rewrite
public signatures or determine whether an arbitrary lifecycle supports DI.
Use scope configuration to state that application contract.
