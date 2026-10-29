# Constructor dependency declarations

The PHP and Laravel sets register `InjectedDependencyRule`. Select application
classes whose retained object dependencies use constructor injection:

```neon
parameters:
    noldors:
        dependencies:
            namespaces: [App\Services, App\UseCases]
            excludedClasses: [App\Services\HydratedAdapter]
```

The scope is empty by default. All four standard scope lists are supported:
`classes`, `namespaces`, `excludedClasses`, and `excludedNamespaces`. Select real
dependency owners rather than all object-valued DTOs or framework data models.

`api.dependencyPromotion` reports a private, typed object property retained through
a direct same-name constructor assignment when promotion has a supported shape:

```php
// Reported: separate declaration and an initial unchanged assignment.
private Repository $repository;

public function __construct(Repository $repository)
{
    $this->repository = $repository;
}

// Accepted declaration.
public function __construct(private readonly Repository $repository) {}
```

Promotion candidates precede other constructor work, have matching native types,
and have no conflicting attributes, property defaults, or property hooks. Inputs
transformed into derived state or passed to a parent without retention are not
promotion candidates. The rule does not rewrite initialization order or PHPDoc.

`api.dependencyReadonly` reports a retained private object dependency reference
with one known constructor write and no subsequent write. It handles promoted
and ordinary properties independently of promotion. Mutating the service object
through its public methods remains valid; `readonly` protects the reference.

Reference escapes, `unset`, unknown dynamic writes and hydration hooks prevent a
readonly recommendation. Trait-declared properties, public/protected state and
unresolved construction patterns are outside this conservative analysis. Keep
explicit hydration, serialization and external mutation contracts excluded.
The rule does not require an entire class to be readonly and cannot prove the
absence of reflection-based mutation outside the analyzed class.

Promotion requires a PHPStan target of PHP 8.0+, readonly requires PHP 8.1+.
Both checks emit ordinary PHPStan diagnostics. Apply declaration changes with
their native type and runtime contracts intact; no automatic rewrite is provided.
