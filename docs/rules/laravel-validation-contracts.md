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

The first version recognizes standard string rules supported by the installed
Laravel validator and these direct built-in object contracts: `Exists`, `Unique`,
`In`, `NotIn`, `Enum`, `ArrayRule` and the base `StringRule` constraint. Unknown
custom string rules, conditional object builders and unrecognized compound
builder constraints are not guessed. Custom subclasses are not assumed to have
their parent's rule semantics. These cases still require review or an explicit
known string rule. The presence of a translation call is accepted without loading
or verifying the runtime translation catalog.

Nested field/rule message maps and size-specific submaps are accepted. A literal
empty message does not establish coverage. Wildcard matching follows Laravel's
single-path-segment message matching; a concrete index message does not establish
coverage for every element of a wildcard rule.

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

The check verifies declarations and keys, not message accuracy, translation
availability, actual failing requests or public error-path behavior. Keep endpoint
regressions for those guarantees. It provides diagnostics without rewriting
validation behavior, messages, method order or public field paths.
