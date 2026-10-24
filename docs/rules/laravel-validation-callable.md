# LaravelValidationCallableRule

Included in the [Laravel set](../README.md). Reports
`architecture.validationLocation` for first-class callable references to Laravel
validation APIs outside classes in allowed directories. It uses the same
placement policy as [LaravelValidationLocationRule](laravel-validation-location.md).

## What it checks

Function, static-method, method and instantiation callable nodes are registered.
References to validation creation, rule builders and mutations must stay in
allowed classes. References to prepared-validator execution and unrelated APIs
remain allowed. PHPStan's own checks for unsupported PHP syntax still apply.

## Example

Rejected in a service outside the allowed validation directories:

```php
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Support\Facades\Validator;

function validationFactories(Factory $factory): array
{
    return [
        validator(...),
        Validator::make(...),
        $factory->make(...),
    ];
}
```

The same references are allowed inside a class located in an
[allowed validation directory](../README.md#allowed-validation-directories).
This closes the first-class callable route for declaring validation outside
validator classes; ordinary validation calls are checked by the location rule.
