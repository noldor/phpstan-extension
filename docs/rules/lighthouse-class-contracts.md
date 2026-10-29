# Lighthouse PHP class contracts

These Laravel-set checks require explicit operation-role scopes and the
consumer's installed Lighthouse classes. They do not infer roles from suffixes.

```neon
parameters:
    noldors:
        laravel:
            lighthouseValidators:
                namespaces: [App\GraphQL\Validators]
            lighthouseResolvers:
                namespaces: [App\GraphQL\Types]
            lighthouseSubscriptions:
                namespaces: [App\GraphQL\Subscriptions]
```

Each scope is empty by default and supports `classes`, `namespaces`,
`excludedClasses`, and `excludedNamespaces`. Select only actual operation roles;
exclude helpers, unrelated query modifiers and required extension contracts.

| Identifier | Contract |
| --- | --- |
| `lighthouse.validatorBase` | Operation validators extend `Nuwave\Lighthouse\Validation\Validator`, directly or indirectly. |
| `lighthouse.validatorFinal` | Concrete operation validators use native `final`. |
| `lighthouse.resolverInvokable` | Custom field handlers expose an effective public `__invoke()`, including an inherited implementation. |
| `lighthouse.subscriptionFinal` | Concrete subscription classes use native `final`. |

Abstract bases are not required to be final or concrete handlers. PHPDoc `@final`
does not replace a native final declaration. Native directive-backed operations,
batch loaders and directives are not custom field handlers merely because they
appear in the same project.

The checks do not prove SDL registration, correct policy selection, recipient
authorization, or subscription delivery. Preserve schema validation and real
boundary tests in the consuming application. Test fixtures use a minimal
Lighthouse validator inheritance stub; the package does not install Lighthouse
as a consumer runtime dependency. No automatic modifier or inheritance rewrite
is provided.
