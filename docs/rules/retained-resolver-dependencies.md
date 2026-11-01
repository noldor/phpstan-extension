# Retained GraphQL resolver dependencies

The Laravel set includes an opt-in constructor and retained-property type-graph
check for applications that retain resolver instances, for example a Lighthouse
schema reused by an Octane worker. It is independent of the ordinary
`lighthouseResolvers` class-contract scope. An empty root scope disables it.
This check does not require Laravel or Lighthouse as production dependencies of
this extension; their installed types are reflected in the consuming application.

## Connection

```neon
includes:
    - vendor/noldors/phpstan-extension/src/laravel.neon

parameters:
    paths: [app, bootstrap, tests]
    noldors:
        laravel:
            retainedResolvers:
                namespaces: [App\GraphQL\Types]
                bindingSourcePaths: [app/Providers, bootstrap]
                implementations:
                    - {abstract: App\Contracts\MailboxRepository, concretes: [App\Infrastructure\SqlMailboxRepository]}
```

Configure `classes`, `namespaces`, `excludedClasses` and `excludedNamespaces` as
with other class scopes. Class selectors include subclasses and namespace
selectors include descendants. Exclusions affect root selection only: an
excluded resolver reached as another resolver's dependency is still checked.
No application namespace or directory name is inferred by the extension.

`bindingSourcePaths` must explicitly select production registration sources.
Paths are files or directories, relative to the PHPStan working directory or
absolute, with directory boundaries respected; they are not globs. Include those
files in PHPStan's analysed `paths`. The extension does not discover application
providers or execute the container. Missing or excluded source files cannot
contribute binding facts. Do not include test container overrides in this scope.
An empty binding-source list produces an unresolved-construction diagnostic.

## Checked graph

The check follows constructor parameter types and backed instance property types
of each reachable concrete class, including inherited/trait constructors and
parent-private properties. Declared generic specializations are preserved when inspecting their
constructor/property types; `Holder<Request>` is not reduced to raw `Holder`.
Generic arguments used only as class-name strings or invocation types are not
automatically retained object edges. Unresolved retained template types and
generic contract mappings without specialized products require explicit review.
Template substitutions for generic implementation mappings are not inferred.
Use a concrete specialized dependency or a separately reviewed concrete owner
boundary when such a mapping cannot be established. Recursion that changes a
generic specialization is diagnosed conservatively instead of expanding an
unbounded type graph; ordinary cyclic object graphs still terminate normally.
Invocation method arguments are outside the retained graph. Static and virtual
properties are outside this check. Scalar properties,
scalar arrays and class-name strings are permitted; array and iterable value
object types are followed. Callable signatures do not imply that their parameter
or return objects have been retained. A retained callable itself needs explicit
review because its captures cannot be established from the signature.

The default forbidden families include requests, GraphQL contexts/execution
information, native Context repositories, actors/auth guards, containers, models,
Eloquent collections, database builders/relations and pending HTTP requests.
`unsafeClasses` appends project-specific families, such as the current tenant
context. Subclasses and implementations are checked against these families.
Use `unsafeClasses!` only when deliberately replacing the defaults.

For example, this chain fails even though the resolver is otherwise stateless:

```php
final class SendMessage
{
    public function __construct(private readonly SendMessageUseCase $useCase) {}
}

final class SendMessageUseCase
{
    public function __construct(private readonly CurrentMailbox $mailbox) {}
}

final class CurrentMailbox
{
    public function __construct(private readonly \Illuminate\Http\Request $request) {}
}
```

Pass actor/input objects as invocation arguments and retain only services whose
construction and state are independent of an operation. `readonly`, `scoped`,
transient resolution and ordinary one-time lazy initialization do not refresh a
reference already retained by the resolver.

## Bindings and reviewed construction

Collectors read finite class registrations using container `bind`, `bindIf`,
`singleton`, `singletonIf`, `scoped`, `scopedIf`, `instance` and `alias` calls,
plus literal provider `$bindings` and `$singletons` properties. Native
registrations through the native `App` facade and application-builder
`withBindings`, `withSingletons` and `withScopedSingletons` are included.
Provider/builder singleton lists with integer keys use each value as its own
abstract, matching Laravel. Class-name bindings and aliases are followed.
An omitted/null concrete for a class binding
means that same class. All observed alternatives are checked; registration order
and runtime conditions are not interpreted. Cyclic aliases without a concrete
construction boundary fail rather than being treated as safe object cycles.
Literal container keys can connect aliases to finite class bindings as well.
Operations Laravel canonicalizes (`extend`, `rebinding`, `when` and resolving
callbacks) apply to the finite canonical targets reached through alias chains,
without retaining a second exact construction requirement at the alias spelling.
Resolving hooks also retain their original inherited applicability, because a
hook registered before an alias can still affect other subclasses. Ordinary bindings
retain their exact targets. Self-aliases and alias cycles fail construction
checks; unresolved alias targets produce an unknown-registration diagnostic.
Identifiers differing only by case are conservatively combined.

Factories, instances, scoped registrations, contextual `when` construction,
container decorators/resolving hooks and Laravel container attributes are
conservative review boundaries. Direct `addContextualBinding`, `offsetSet` and
container array assignments are also included. Conditional `??=` assignments
also require construction review;
nullable-container calls are analysed through PHPStan's normalized call nodes.
Named arguments follow the native method parameter names. Literal null map
values are finite self-bindings. Before/resolving/after callbacks apply to reached
subclasses and implementations, matching Laravel; ordinary bindings stay exact.
Unpacked call arguments remain
unresolved rather than being mistaken for omitted arguments. Global attribute
resolution hooks have no finite constructed abstract and fail closed.
A known abstract is diagnosed when reached.
A dynamic abstract or unbounded/global registration in the selected sources
produces `lighthouse.retainedUnknownBinding`, since it could affect any reached
dependency. Calls on receivers whose container type cannot be established and
arbitrary custom registration wrappers are not interpreted.

`implementations` records finite implementation sets for contracts whose native
registration is outside analysed sources. It does **not** clear a discovered
factory or contextual/scoped construction boundary. Use a separate assertion
after reviewing its construction behavior:

```neon
parameters:
    noldors:
        laravel:
            retainedResolvers:
                reviewedFactories:
                    - {abstract: App\Contracts\ApiClient, concretes: [App\Infrastructure\FixedApiClient]}
```

This asserts that the complete construction path produces only those classes,
with operation-independent arguments and mutations. The resulting constructor
and property graphs are still checked. A declared factory product is inspected
as a constructed object, rather than resolving its class name through the
container again. This permits a canonical factory behind a class alias without
creating a false resolution cycle. The complete-path assertion includes
applicable extenders and resolving callbacks; it does not assert arbitrary
future resolutions of the product class. Constructor/property dependencies still use
normal container resolution. Actual alias/binding cycles on unresolved paths
remain errors, even when their target names also occur in the product list.
Observed native binding/alias edges and configured implementation mappings
remain container resolutions; a reviewed product declaration does not erase
those alternatives. Required scalar factory inputs are
accepted under that construction assertion; mixed/object/callable retained
state remains unresolved. For direct custom construction of a concrete class,
use the same concrete as `abstract` and in `concretes`. Empty product lists do
not grant an exemption. A resolver mapping alone never validates arbitrary code
inside a factory, hooks or contextual attributes.
For aliases discovered in the selected binding sources, configure
implementation/factory assertions against the canonical abstract (including a
literal container key when the application uses one), rather than an alias
spelling. Alias normalization does not turn an implementation mapping
into a construction assertion.

`safeClasses` declares exact **concrete** terminal boundaries whose construction,
internal retained state and lifecycle support have been reviewed separately:

```neon
parameters:
    noldors:
        laravel:
            retainedResolvers:
                implementations:
                    - {abstract: Illuminate\Contracts\Config\Repository, concretes: [Illuminate\Config\Repository]}
                safeClasses: [Illuminate\Config\Repository]
```

This example assumes application configuration is immutable for the entire
worker lifetime. The contract is resolved before inspecting the terminal;
listing an interface in `safeClasses` does not bless its implementations.
Subclasses are not automatically safe. Forbidden type families are checked
before any terminal assertion. Discovered opaque construction still requires its
own `reviewedFactories` assertion. Do not allowlist framework classes broadly:
Gate, auth managers or validation factories require the application's actual
Octane rebinding/reset mechanisms and worker regression coverage to remain safe.

## Diagnostics and static limits

| Identifier | Meaning |
| --- | --- |
| `lighthouse.retainedUnsafeDependency` | A reachable declared type belongs to a forbidden operation-bound family. |
| `lighthouse.retainedUnknownDependency` | A construction path or retained type cannot be established without explicit review/configuration. |
| `lighthouse.retainedUnknownBinding` | A selected registration has no finite abstract target. |

A deterministic breadth-first traversal reports the first failing path for each
root at the resolver declaration, including the root-to-dependency chain. Fix it
and rerun to expose any remaining paths. Cyclic object type graphs terminate;
one root's traversal does not mark a shared subgraph safe for another root.
Native named class parameters can be autowired. Required primitive parameters,
union/intersection construction, contextual attributes and unresolved
interfaces require construction review. Non-public constructors also need
reviewed factory construction. Invocation parameters are unaffected.

This is a conservative **type-graph check**, not a proof of semantic statelessness
or a Laravel container interpreter. For example, assigning
`request()->user()->id` to a declared string property can evade it: neither the
constructor signature nor the property type mentions the request. Scalar
memoization, hidden runtime subtypes, global/static state, method-side mutation,
unanalysed registrations, manually assembled dependency graphs and code inside
reviewed factories/boundaries still require review and suitable worker tests.
Adding a broad terminal or replacing forbidden families weakens the guarantee.
