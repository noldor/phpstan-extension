# Use-case validation boundary

`LaravelUseCaseExceptionRule` and `LaravelUseCaseValidationExtension` report `architecture.useCaseValidation` inside the configured `noldors.laravel.useCases` scope. See [scope configuration](../laravel-application.md).

The checks reject resolved validation-exception throws, direct construction of validation error bags, known `ValidationException::withMessages()` calls/references, and execution of a prepared Laravel validator through `validate()` / `validateWithBag()`. The adapter owns these operations; use cases expose invariant failures through appropriate domain exceptions.

```php
// Rejected inside a selected use case.
throw ValidationException::withMessages(['name' => 'Name is required.']);

// A use-case invariant failure can use a domain exception.
throw new DomainException('The order cannot be submitted.');
```

Catch declarations, type annotations, and merely constructing an exception value that is not thrown are not prohibited by this rule. Existing validation-location checks still own where rule declarations belong, and the existing previous-exception rule still checks wrapping.

The configured defaults are:

```neon
parameters:
    noldors:
        laravel:
            validationExceptions: [Illuminate\Validation\ValidationException]
            errorBagClasses: [Illuminate\Support\MessageBag, Illuminate\Support\ViewErrorBag]
```

Subclasses are included. Append the application's separate validation-exception base if it does not extend Laravel's exception. `withMessages` is treated as the error-bag-building API on these selected exception types. Arbitrary factories and validation hidden inside unselected service helpers are not transitive effect analysis.
