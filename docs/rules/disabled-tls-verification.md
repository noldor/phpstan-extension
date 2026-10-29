# Disabled TLS verification

Identifier: `security.disabledTlsVerification`.

The PHP set reports statically proven disabling of certificate or peer-name
verification in native cURL/SSL stream options and Guzzle client configuration
or request options. The Laravel set additionally checks `Http` factories,
pending requests, `withoutVerifying()` and `withOptions()`.

```php
// Reported.
new GuzzleHttp\Client(['verify' => false]);
curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
Http::withoutVerifying(); // Laravel set.

// Accepted: retain peer verification, including private trust roots.
new GuzzleHttp\Client(['verify' => '/etc/internal-ca.pem']);
```

Arguments may be positional or named; constant options arrays and resolved
aliases are supported. Merely taking a first-class callable is not execution.
Unrelated methods with matching names are accepted. Unknown option values are
not reported as proven disabling. Guzzle CA path strings remain valid.
Native cURL verification flags use integer conversion: values such as `'0.0'`
or `0.5` become zero and disable verification. SSL stream flags use Boolean
conversion, so statically false values such as `0` disable verification while
the string `'0.0'` remains enabled. Guzzle's `verify` option is checked for
Boolean `false` only, preserving valid CA path strings such as `'0'`.

The native adapters cover `curl_setopt`, `curl_setopt_array`,
`stream_context_create`, `stream_context_set_options` and the supported scalar
and array forms of `stream_context_set_option`. The cURL option numbers are the
stable libcurl `SSL_VERIFYPEER` (64) and `SSL_VERIFYHOST` (81) identifiers; the
extension does not require ext-curl in its own runtime.

Configure deliberate test-only exceptions with exact files or directories:

```neon
parameters:
    noldors:
        disabledTlsVerification:
            excludePaths: [tests/Fixtures/InsecureTls]
```

Paths are relative to PHPStan's working directory or absolute. A directory
matches descendants, not similarly named neighbouring directories. There are
no default test exclusions. `laravelClients` is false in the PHP set and true in
the Laravel set.

This is a source policy check, not proof of exploitable interception. It does
not trace arbitrary factories, environment overlays, redirects or retry paths.
There is no automatic fix: a client may need an explicit trusted CA.
