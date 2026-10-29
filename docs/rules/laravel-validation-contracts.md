# Laravel validation contracts

Enabled by the Laravel set for classes selected by
`noldors.laravel.validators`. The scope is empty by default. Select validator
classes or namespaces and use its normal class/namespace exclusions for explicit
exceptions. No application namespace or relationship name is built into the rules.

| Proposal | Error identifier | Contract |
| --- | --- | --- |
| V1 | `validation.methodOrder` | Declare existing methods in `rules()`, `messages()`, `attributes()` order. Other methods may appear between them. Inherited methods do not need redeclaration. |
| V2 | `validation.fieldOrder` | Put ordinary fields, including direct identifiers, before configured relationship groups. Keep each group contiguous and declared parent paths before their children. A dot alone does not make a field a relationship. |
| V3 | `validation.messageOrder` | Keep message groups in `rules()` field order, including nested message maps. |
| V3 | `validation.attributeOrder` | Keep existing attribute labels in `rules()` field order. Labels are optional. |
| V4 | `validation.messageCoverage` | Provide an explicit field/rule message for each statically known failing built-in rule. Wildcards and translation calls are accepted. Application rule objects and closures own their public failure messages. |
| V5 | `validation.lighthouseMessageKey` | In Lighthouse validators, replace generic built-in keys such as `exists` with field-specific keys such as `ownerId.exists`. |
| V6 | `validation.relativeInputPath` | For explicitly configured input-bound validators, remove an extra `input.` prefix when the following segment names a known field of that input. |

The rule reports contracts on the selected concrete class. Abstract bases and
traits provide inherited definitions without being required to declare operation
messages themselves. Effective inherited methods, trait methods and trait aliases
are inspected through PHPStan reflection and parsed source. No validator or
application method is executed.

## Configuration

```neon
includes:
    - vendor/noldors/phpstan-extension/src/laravel.neon

parameters:
    noldors:
        laravel:
            validators:
                namespaces: [App\GraphQL\Validators, App\Http\Requests]
            validationContracts:
                relationships:
                    - class: App\GraphQL\Validators\Orders\UpdateValidator
                      fields: [items, tags]
                inputValidators:
                    - class: App\GraphQL\Validators\Orders\UpdateValidator
                      fields: [title, ownerId, items, tags]
```

Both lists default to empty. Entries select a class and its subclasses; matching
entries are combined. Relationship fields are public path roots, such as `items`,
not Eloquent relationship method guesses. Their configured order does not dictate
the order of independent relationship groups.

`inputValidators` records a **known input binding** and that input's actual
top-level public field names. Do not add a field-bound validator to this list.
If the input really has a field named `input`, include it: `input.ownerId` can then
be a legitimate nested path and is accepted. With known fields `[ownerId]`, the
same path is diagnosed in rules, messages and attributes. Unknown bindings and
unknown field names are left for schema-aware review. This check does not infer
binding from a class suffix, directory or the word `input` alone.

## Explicit messages and custom rules

```php
public function rules(): array
{
    return [
        'ownerId' => ['bail', 'required', 'integer', Rule::exists('users', 'id')],
        'items' => ['array'],
        'items.*' => ['required', 'integer', new MaySelectItem()],
    ];
}

public function messages(): array
{
    return [
        'ownerId.required' => __('validation.required'),
        'ownerId.integer' => __('validation.integer'),
        'ownerId.exists' => __('validation.exists'),
        'items.array' => __('validation.array'),
        'items.*.required' => __('validation.required'),
        'items.*.integer' => __('validation.integer'),
    ];
}
```

`MaySelectItem` reports its own public failure through its validation-rule
contract, for example `$fail()`. No class-name message key is required for it.
`bail`, `nullable`, `sometimes` and exclusion controls do not need artificial
messages. Standard aliases `int` and `bool` use the `integer` and `boolean`
message keys. Strings inside rule arrays keep regex pipes intact; top-level
pipe-delimited rule strings follow Laravel's normal splitting behavior.

Standard string rules are recognized through the installed Laravel validator.
Literal native factory calls and built-in rule objects retain their own message
requirements. This includes presence, acceptance, containment, comparison,
database, authorization, enum, regular expression, format and dimension rules.
`Rule::string()->min(3)`, for example, requires both `field.string` and
`field.min`; `Rule::numeric()` requires `field.numeric`, and
`Rule::dimensions(...)` requires `field.dimensions`.

The check also reads statically declared fluent constraints on Laravel's
`StringRule`, `Numeric`, `Date`, `File`, `ImageFile`, `Email`, `Password` and
`IpAddress` builders. Date formats, file MIME types/extensions/size bounds,
password complexity, additional `rules(...)` and literal boolean
`Rule::when(...)`/`Rule::unless(...)` branches are included. Repeated setters
follow the native replacement or append behavior. File, email and password rules
run their internal validator in a separate rule context; an outer `numeric` or
`bail` declaration does not change that internal context. No rule object or
application callback is executed.

Implicit native constraints are included: numeric `digits(...)`,
`digitsBetween(...)` and `exactly(...)` also require an `integer` message.
Empty tokens in string rule definitions are ignored, matching Laravel.

Native rule objects use Laravel's actual class-key message contract. For
`Can`, `Enum` and `AnyOf`, a message such as
`'status.' . Illuminate\Validation\Rules\Enum::class` covers every failing input.
Laravel also accepts the short `status.enum` alias when its message type is not
`file`; the check accepts that alias when numeric/array rules, or `bail` with an
earlier string rule, guarantee a non-file type. Otherwise the full class key
covers the possible file branch. An explicit full class-key override for `File`,
`ImageFile`, `Email` or `Password` also covers all that object's nested failures;
individual nested messages remain a supported alternative. Generic native
class keys and generic `password.mixed`/`password.letters`/`password.numbers`/
`password.symbols`/`password.uncompromised` keys are rejected for Lighthouse
validators. Add the public field prefix.

Nested field/rule message maps and Laravel's size-specific submaps are accepted
only when they cover the branches that can fail. A direct `field.max` message
covers every branch. A numeric rule can use `field.max.numeric`; array/list rules
can use `field.max.array`. Other size checks may receive either a string or a
file, so provide both `string` and `file` entries unless `bail` and an earlier
string/file constraint guarantee a single branch. A `string` rule alone does not
prevent a later size rule from failing on an uploaded file. An arbitrary
descendant such as `field.required.unused` never covers `field.required`.
Earlier native `File` and `ImageFile` rules also guarantee the file branch when
combined with `bail`.

Comparisons `gt`, `gte`, `lt` and `lte` can select a numeric message even without
an explicit numeric rule. Their submaps must include that possible branch unless
`bail` and an earlier file/array constraint exclude numeric input. Laravel resets
its numeric comparison state before the next built-in rule, so a comparison does
not add a numeric branch to another `min`, `max`, `size` or `between` rule.

Literal empty, null and false messages do not establish coverage. Translation calls
are accepted without loading or verifying the runtime translation catalog.
Wildcard matching follows Laravel's single-path-segment message matching; a
concrete index message does not establish coverage for every element of a
wildcard rule.

## Static analysis limits

The check understands literal arrays, constant string keys, class constants known
to PHPStan, straight-line local assignments, array unpacking and array `+` merges.
It preserves source insertion order for unpacking and merges instead of relying
on inferred array-type key order. Dynamic branches, loops, unknown array keys,
unresolved helper composition and calls receiving composed local variables are
treated as unknown. Numeric root keys are also left outside the string-path
contract. An unresolved `messages()` return does not prove that messages
are missing. Unknown rule objects do not suppress checks for other known standard
rules in the same field's literal rule list.

Compound builder analysis requires a literal native constructor or factory and
its directly chained calls. Rules added by an application factory, later object
mutation, a macro, a callback, `Rule::forEach(...)`, an unresolved conditional
branch or callback-configured password defaults remain unknown. Dynamic file
types or bounds do not invent a particular MIME or size constraint. Known base
rules and other explicitly resolved constraints are still checked. Custom
subclasses are not assumed to have their parent's semantics, and unknown custom
string rules are left to application-specific checks. These unresolved portions
require review or explicit known rule declarations.

The check verifies declarations and keys, not message accuracy, translation
availability, actual failing requests or public error-path behavior. Keep endpoint
regressions for those guarantees. It provides diagnostics without rewriting
validation behavior, messages, method order or public field paths.
