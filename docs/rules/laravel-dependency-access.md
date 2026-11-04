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
lookups through global facade guard/driver chains, `request()->user()` and the
Request facade. Imported facade aliases,
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
- Global service-acquiring Laravel helper calls and first-class function
  callables, such as `app`, `resolve`, `auth`, `config`, `cache`, `request` and
  `dispatch`. Facade-backed helpers `now`, `today` and `uri` also acquire services.
  The default `allLaravelHelpers: false` accepts utility helpers
  such as `collect`, `data_get`, `blank` and `value`. Native PHP functions and
  user-defined namespaced functions with the same short names remain accepted.
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

The default service helper set is listed in `LaravelHelperFunctions::SERVICES`.
For an explicitly stricter policy, set `allLaravelHelpers: true` to include
Laravel utility helpers. `helpers` adds explicitly named helper APIs in either
mode. All-helper mode recognizes global functions by their Laravel helper source
path and uses a known Laravel helper-name catalog for reflected signatures whose
original source is unavailable. Recognition reads reflection metadata only; it
does not load application code or scan/execute arbitrary filesystem files.

### Allow a helper only in selected methods

`dependencyInjection.allowedHelpers` defaults to `[]`. Each entry permits one
exact function only in the listed methods of selected classes or namespaces.
The surrounding `dependencyInjection` selection still determines where DI is
checked. For example, Lighthouse can retain a resolver between Octane requests;
resolve request-scoped services during `__invoke()` instead of retaining them in
the resolver constructor:

```neon
parameters:
    noldors:
        laravel:
            dependencyInjection:
                namespaces: [Company\GraphQL\Resolvers]
                allowedHelpers:
                    -
                        function: resolve
                        classes: [Company\GraphQL\Resolvers\Login]
                        methods: [__invoke]
                    -
                        function: resolve
                        namespaces: [Company\GraphQL\Resolvers\Queries]
                        methods: [__invoke]
```

```php
final class Login
{
    public function __invoke(): void
    {
        $operation = resolve(LoginOperation::class); // Allowed by this entry.
        $operation->handle();
    }

    public function other(): void
    {
        resolve(LoginOperation::class); // Still rejected, as in __construct().
    }
}
```

`function` and `methods` are required; `classes` and `namespaces` are optional
lists, combined with OR when both are supplied. Class selectors include
subclasses and interface implementations; namespace selectors respect namespace
boundaries. An entry without class/namespace selectors or with an empty method
list grants no allowance. Function and method names are case-insensitive; an
optional leading `\` on the function name is accepted. Use fully qualified names
for custom namespaced helpers. Names are exact, with no wildcard matching.
PHPStan validates the entry structure and field types before analysis.

The allowance covers direct calls, imported function aliases and first-class
function callable creation such as `resolve(...)` in the selected method.
Closures and arrow functions nested inside it do not inherit the allowance;
neither do other methods or constructors unless explicitly listed. Selection
uses the class and method where PHPStan analyses the call, not the eventual
runtime caller. Inherited method bodies keep their declaring analysis context.

Only the named helper's `architecture.dependencyInjection` diagnostic is
suppressed. Other helpers (including those selected by `allLaravelHelpers`),
facade references, service locator calls/offsets and other rules remain active.
In particular, `retainedResolvers` continues checking retained dependencies; this
setting does not make a resolved service safe to retain on the resolver.

This uses PHPStan's [restricted function usage extension](https://phpstan.org/developing-extensions/restricted-usage-extensions#functions).
As with the existing helper restriction, arbitrary variable function calls,
`call_user_func()` indirection and execution of previously created callables are
not traced. The allowance applies to the analysed named call or callable
creation; it does not validate where a callable is later invoked.

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
