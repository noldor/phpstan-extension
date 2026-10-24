# LaravelValidationLocationRule

Included in the [Laravel set](../README.md). Reports
`architecture.validationLocation` when Laravel validation is declared outside a
class in an allowed directory.

## What it checks

- The `validator()` helper and Laravel validation factories and facades.
- Request and controller validation methods.
- Validation rule construction and rule-builder mutations.
- Registration of validation callbacks and extensions.
- `rules()` declarations in FormRequest subclasses and Lighthouse validators,
  regardless of their namespace.

Executing a prepared validator and unrelated methods remain allowed. A class is
required even in an allowed directory: global-scope declarations are rejected.

## Example

Rejected in `app/Services/RegisterUser.php`:

```php
namespace App\Services;

use Illuminate\Support\Facades\Validator;

final class RegisterUser
{
    public function run(array $input): void
    {
        Validator::make($input, ['email' => 'required|email'])->validate();
    }
}
```

Declare the validation in an allowed class instead, for example
`app/Http/Requests/RegisterUserRequest.php`:

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RegisterUserRequest extends FormRequest
{
    public function rules(): array
    {
        return ['email' => 'required|email'];
    }
}
```

See [allowed validation directories](../README.md#allowed-validation-directories)
for defaults and configuration. First-class references to validation APIs are
covered by [LaravelValidationCallableRule](laravel-validation-callable.md).
