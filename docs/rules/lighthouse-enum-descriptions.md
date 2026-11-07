# Lighthouse GraphQL enum descriptions

The Laravel set requires `GraphQL\Type\Definition\Description` on PHP enums
registered as webonyx `PhpEnumType` instances and on every case in those enums.
The rule uses the resolved attribute name, including imports and aliases.
PHPDoc and the optional constructor description do not satisfy this requirement.
The rule checks attribute presence.

The registration APIs were verified with Lighthouse 6.74.0 and
webonyx/graphql-php 15.39.0.

```php
use GraphQL\Type\Definition\Description;
use GraphQL\Type\Definition\PhpEnumType;
use Nuwave\Lighthouse\Schema\TypeRegistry;

#[Description('The account status.')]
enum AccountStatus: string
{
    #[Description('The account can sign in.')]
    case ACTIVE = 'active';

    #[Description('The account is disabled.')]
    case DISABLED = 'disabled';
}

function registerAccountStatus(TypeRegistry $types): void
{
    $types->register(new PhpEnumType(AccountStatus::class, 'AccountStatus'));
}
```

PHPStan reports `lighthouse.enumDescription` on the enum declaration and
`lighthouse.enumCaseDescription` on each case missing the attribute.

The rule matches direct `TypeRegistry::register()` and `TypeRegistry::overwrite()`
calls whose argument is an inline `new PhpEnumType(...)`. The receiver type must
resolve to the Lighthouse registry and the constructor's `enumClass` argument
must resolve to a single constant class name. Positional and named arguments
are supported. Include both the registration and enum files in the analysis.

For `registerLazy()` and `overwriteLazy()`, the rule also recognises an inline
arrow function or a closure with a single return statement that directly
returns `new PhpEnumType(...)` with an enum class literal.

Registrations through variables holding type objects, separate factories or
callbacks, dynamically chosen constructor classes, argument unpacking, and
class names that PHPStan cannot resolve to a single value are outside the
analysis. SDL-defined enums and ordinary PHP enums without a matching
registration are outside the rule's scope.
