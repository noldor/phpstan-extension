# Preserve previous exceptions

`PreservePreviousExceptionRule` reports `exceptions.missingPrevious` when a catch
block directly throws a new exception without preserving the caught exception
in the supported constructor's cause parameter.

```php
try {
    $gateway->charge($payment);
} catch (GatewayException $error) {
    // Rejected: the message does not preserve the original exception.
    throw new PaymentException($error->getMessage());
}

try {
    $gateway->charge($payment);
} catch (GatewayException $error) {
    // Accepted.
    throw new PaymentException('Payment failed', previous: $error);
}
```

Rethrowing the caught exception is accepted. A local alias assigned once from
the caught exception before the throw is accepted too.

## Constructor contracts

The rule resolves the exception class and its actual constructor. It supports
both positional and named arguments; it does not assume that the cause is the
third argument. A parameter whose non-null type is a `Throwable` subtype is a
cause candidate. The parameter named `previous` takes precedence; otherwise the
constructor must have exactly one cause candidate. A custom parameter named
`cause` therefore works without extra configuration.

Passing the exception as an unrelated message or context argument does not
satisfy the rule. Directly nested exception constructors preserve the cause only
when their own supported cause parameters also preserve it.

Constructor argument flow is the boundary of this check. It trusts the declared
cause parameter's contract and does not prove that a custom constructor forwards
it to its parent.

## Analysis boundaries

- The rule checks direct `throw new NamedException(...)` expressions inside a
  catch block, including ordinary branches. It leaves throws in nested try
  blocks to that operation and analyses their catch blocks independently.
  Sibling throws in the original catch are still checked.
- Nested functions, closures and classes form separate scopes. Their unrelated
  throws are not attributed to the surrounding catch.
- A catch is left unchecked when its caught variable is reassigned, unset,
  destructured, captured by reference, or passed through a resolvable
  by-reference function, method or constructor argument. This conservative
  boundary avoids assuming that the current variable still holds the caught
  exception. Alias tracking is local and does not model arbitrary control flow.
  When a call target cannot be resolved from the catch entry scope, its argument
  variables are treated as possible reference writes. The same conservative
  treatment applies to unpacked arguments; variadic reference parameters cover
  all corresponding positional and named arguments.
  Entry-scope receiver types are trusted only for simple variables without
  syntactic writes anywhere in the catch. Reassigned receivers, receiver
  expressions and dynamic class/callable expressions are treated as unresolved,
  rather than inferring a replacement type from the catch body.
- Dynamic exception classes, constructors without an unambiguous typed cause
  parameter, argument unpacking and indirect factories are outside the check.
  A factory or another nontrivial expression supplied as the cause is also left
  unchecked: passing an exception to an arbitrary function is not proof that
  the function preserves its chain.
  An unproven cause variable is left unchecked too, since it may already contain
  a wrapper that preserves the original exception.
- Catches without a variable are outside the check. The rule does not require
  recovery paths to throw or decide whether an independently raised exception
  inside a catch is a domain-appropriate translation.

These boundaries mean the rule detects common loss of exception context; it is
not a complete proof of exception-chain preservation.

## Registration

The PHP and Laravel sets enable this rule. Its constructor receives PHPStan's
`ReflectionProvider` through dependency injection and needs no extra options.
