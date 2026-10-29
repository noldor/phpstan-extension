# Side effects in runtime assertions

`NoSideEffectsInAssertRule` reports `assert.sideEffect` for assignments,
reference assignments, compound assignments, increments/decrements and selected
infrastructure calls evaluated inside PHP's built-in `assert()` arguments.
Assertions can be disabled in production, including evaluation of their
arguments. Required work must execute independently of assertion settings.

```php
// Rejected.
assert($order->save());
assert(++$attempts > 0);
assert(assertion: $valid, description: file_put_contents($path, $details));

// Accepted: the write executes independently of the internal invariant.
$order->save();
assert($order->exists);
```

The first example requires the corresponding persistence API in the configured
method selectors. The rule does not infer effects from a method name such as
`save` alone. Ordinary internal assertions remain allowed.

## Configuration

The PHP and Laravel sets enable the rule. Extend their API selectors for an
application-owned write or authorization API:

```neon
parameters:
    noldors:
        assertions:
            forbiddenMethods:
                - {class: App\Infrastructure\OrderWriter, methods: [save]}
                - {class: App\Authorization\Access, methods: [authorize]}
            forbiddenFunctions:
                - App\Infrastructure\publish
```

Selectors follow the existing restricted-API contract: class/interface and
method names are case-insensitive, class descendants and interface
implementations match, and `'*'` selects all methods, including `__construct`.
Function names are exact fully qualified names. A leading namespace separator
is optional. Select the declaring parent API when a method is inherited.
Use `forbiddenMethods!` or `forbiddenFunctions!` to replace inherited lists.
Clearing the lists does not disable syntactic assignment/increment checks.

The public constructor accepts `ReflectionProvider $reflectionProvider`,
`list<array{class: string, methods: list<string>}> $forbiddenMethods` and
`list<string> $forbiddenFunctions`. Register
`Noldors\PHPStan\Assertions\AssertArgumentVisitor` with
`phpstan.parser.richParserNodeVisitor` alongside a manually configured rule.
After PHPStan resolves function imports, the visitor annotates each file with
explicit traversal context and retains no mutable service state between files.

## Detection boundaries

- Both the assertion and description arguments are checked for named built-in
  assertion calls, including named arguments and `use function` aliases.
  Infrastructure calls inside those arguments also support a single resolved
  constant function/method/class name.
- Namespace imports are resolved using PHPStan reflection. A different
  function imported under the name `assert` does not receive this restriction.
- Selected instance, nullsafe, static, function and constructor calls are
  checked using the scope at the operation itself. Branch narrowing and
  immediately invoked closure/arrow-function bodies retain their local types.
- Deferred closure, arrow-function and class method bodies are separate
  execution boundaries. Creating a callable for a selected API does not invoke
  that API; its evaluated receiver/arguments are still ordinary expressions.
  An assertion inside a deferred body is checked independently.
- Directly invoked ordinary closure/arrow-function bodies are checked. A
  generator's body remains deferred when its closure is invoked; its call
  arguments still execute eagerly and are checked. Generator detection ignores
  yields in nested functions and classes, so a nested generator does not defer
  the surrounding ordinary closure. Subsequent generator iteration is not
  traced, even when a consumer iterates it inside the assertion expression.
  Calls through an arbitrary callback, a closure variable, a helper or an
  anonymous constructor do not trigger transitive body analysis. Select the
  responsible API when its call itself must be forbidden.
- Unknown dynamic targets and effects hidden behind unselected helper calls,
  magic property access or unconfigured infrastructure APIs remain outside
  the check. Passing analysis is not a proof of purity or production guard
  completeness.
- Variable-function outer calls such as `$assertion($value)` are outside this
  rule, even when the variable currently contains `'assert'`. Reassigning the
  callable variable while evaluating arguments must not misidentify a different
  outer operation as a native assertion.

Diagnostics identify the offending operation. The rule does not move code or
otherwise fix it automatically: the required evaluation and error ordering
must be preserved deliberately.
