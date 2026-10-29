# Laravel application enums

Select application enums and their directory roots in the Laravel set:

```neon
parameters:
    noldors:
        laravel:
            enums:
                namespaces: [App\Enums]
                excludedClasses: [App\Enums\Integration\ExternalStatus]
            enumDirectories: [app/Enums]
```

The class scope and directory roots are empty by default. The scope supports
`classes`, `namespaces`, `excludedClasses`, and `excludedNamespaces`; namespace
matching respects segment boundaries. Directory roots resolve from the PHPStan
working directory, without assuming a consumer's namespace or application root.

| Identifier | Required declaration |
| --- | --- |
| `laravel.enumName` | An application enum name ends with `Enum`. |
| `laravel.enumCase` | Cases use `UPPER_SNAKE_CASE`, such as `PENDING_APPROVAL`. |
| `laravel.enumLocation` | The file is below a domain subdirectory of a configured enum root. |

For example, `app/Enums/Orders/StatusEnum.php` is accepted, while an enum directly
in `app/Enums/` lacks the domain directory. With no directory roots configured,
the name and case checks still run for selected enums, but location is unknown.

Exclude established public/integration enum contracts explicitly. Backed values
are not changed. There is no automatic rename or move: enum class and case names
can be used by consumers, serialization, reflection and public schema mappings.
