# Reference parameters in owned APIs

`NoReferenceParameterRule` reports `api.referenceParameter` for explicit `&`
parameters on owned named functions and methods, including constructors,
interfaces and variadic parameters. Return computed results instead of changing
the caller's variable through a reference parameter.

```php
// Rejected.
function calculateTotal(Order $order, int &$total): void
{
    $total = $order->subtotal() + $order->tax();
}

// Accepted.
function calculateTotal(Order $order): int
{
    return $order->subtotal() + $order->tax();
}
```

Objects do not need `&` to expose their mutation operations. Ordinary object
parameters and explicit object mutation remain accepted. An object parameter
declared with `&` is rejected because it also permits rebinding the caller's
variable.

```php
function applyDiscount(Order $order): void
{
    $order->applyDiscount();
}
```

## Scope and external contracts

The PHP set supplies PHPStan's `paths` as the rule's owned paths. Each path can
identify a directory or an individual file. A path matches its own directory
segments, rather than similarly named neighbouring directories.

A by-reference parameter required by an interface or non-private parent method
declared outside the owned paths is accepted. The exception applies only to the
matching parameter position that the external declaration passes by reference;
an external ancestor does not exempt unrelated methods or added reference
parameters. An external by-reference variadic parameter also covers compatible
implementation parameters that expand its positions. A concrete parent's
constructor does not impose its signature on a child constructor; an abstract
constructor or interface contract can do so.

Closures and arrow functions are outside this rule's declaration scope. This
preserves anonymous callbacks used by external APIs. Named callbacks are treated
as owned named declarations; the rule does not infer external callback contracts
from registrations elsewhere. Calls to external APIs such as `preg_match()` are
not checked. Return-by-reference declarations are also outside this rule's scope.

The rule deliberately rejects all explicit reference parameters in this scope,
including input/output mutation APIs. It does not attempt to infer whether a
parameter is output-only. Required external contracts remain exempt as described
above.

## Registration

The PHP and Laravel sets enable this rule. Its service constructor accepts
`FileHelper $fileHelper` and `list<string> $ownedPaths`; the shared set passes
`%paths%` for the latter. Declare the paths that this project owns, rather than
including dependency directories merely to make their signatures owned.
