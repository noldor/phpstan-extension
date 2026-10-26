# JsonThrowOnErrorRule

Included in the [PHP and Laravel sets](../README.md). Reports
`json.missingThrowOnError` when a call to native `json_encode()` or
`json_decode()` does not provably include `JSON_THROW_ON_ERROR` in its flags.
The check applies to all analysed files, including tests, and has no additional
configuration.

## Preserve existing flags

Add the throw flag using bitwise OR while keeping flags required by the caller:

```php
$encoded = json_encode(
    $value,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
);

$decoded = json_decode(
    $json,
    associative: true,
    flags: JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR,
);
```

The flags position is the second argument for
[`json_encode()`](https://www.php.net/manual/en/function.json-encode.php) and the
fourth for [`json_decode()`](https://www.php.net/manual/en/function.json-decode.php).
Named arguments can appear in any valid order. Passing the flag as
`json_decode()`'s third argument changes its depth instead of enabling exceptions
and is reported.

There is no automatic fix: adding exceptions can require changes to the caller's
error handling. The check also cannot determine which representation flags a
consumer needs, so it never proposes replacing them.

## What proves the flag is present

- A constant integer value, including imported constants and combined flags,
  whose throw bit is set.
- A variable or expression with a finite inferred set of constant integers when
  every possible value contains that bit.
- A bitwise OR expression with a proven throw bit on either side, such as
  `$flags | JSON_THROW_ON_ERROR`.

Unknown dynamic flags are reported. Make the contract explicit at the call:

```php
json_encode($value, $flags | JSON_THROW_ON_ERROR);
```

Unpacked arguments do not prove the flags value. Supply an explicit named
`flags:` argument after the unpacked arguments when appropriate.

## Name resolution and limits

The rule resolves native function names through PHPStan reflection, including
imports, aliases, namespace fallback and fully qualified calls. Unrelated
namespaced functions named `json_encode` or `json_decode` remain allowed. A
dynamic call is checked when PHPStan infers one exact native function name from
its callable string. Arbitrary closures and unresolved dynamic callables are not
followed.

Creating a first-class callable such as `json_encode(...)` does not execute JSON
encoding and has no flags argument, so it is not reported. Invocation through
the resulting closure is outside this direct-call check. Use a closure that
supplies the flag when handing JSON conversion to another operation.

The rule enforces presence of the requested bit. PHP documents that
[`JSON_PARTIAL_OUTPUT_ON_ERROR` takes precedence](https://www.php.net/manual/en/json.constants.php#constant.json-throw-on-error)
over `JSON_THROW_ON_ERROR`; the combination is accepted, so the check alone does
not guarantee exceptions for encoding when partial output is requested.
