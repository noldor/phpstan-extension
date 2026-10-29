# Full request logging

Identifier: `logging.fullRequestPayload`.

The PHP set checks the eight PSR log-level methods and `log()` on configured
logger classes. It reports a whole request object or direct full body source
passed as the message or within literal context arrays, including nested and
unpacked array items. `$_POST` and `$_REQUEST` are direct full input sources.

```php
// Reported.
$logger->info('received', ['body' => $request->all()]);
$logger->warning('received', ['request' => $request]);

// Accepted: deliberately selected fields.
$logger->info('received', ['order_id' => $request->input('orderId')]);
$logger->info('received', ['fields' => $request->only(['orderId'])]);
```

The PHP defaults recognize PSR logger implementations, Symfony Request and PSR
ServerRequestInterface objects, and their configured full-body methods. The
Laravel set adds the Log facade and Request `all()`/`json()` methods. Explicit
key selection such as `all(['orderId'])` or `json('orderId')` is accepted;
omitted or statically null selection means the whole input. Laravel `all()` also
returns the whole input for falsey selections such as `[]`, `false`, `0` or `''`;
`json(default: ...)` still omits its key and returns the full JSON input bag.
Ordinary null values in log context remain accepted. Deferred closures
and first-class callable creation are not executed payload reads.

Configuration is explicit and validated:

```neon
parameters:
    noldors:
        fullRequestLogging:
            loggerClasses: [Company\Logging\Logger]
            requestClasses: [Company\Http\Request]
            requestMethods:
                - {class: Company\Http\Request, methods: [fullBody]}
            excludePaths: [tests/Fixtures/LogCapture]
```

Lists append to defaults; use the NEON `!` suffix to replace them. Configured
loggers must follow PSR message/context positions and parameter names. Request
methods identify direct full-payload sources, not every method with an `all`
name. Exclusions use exact files/directories relative to the working directory
or absolute paths. There are no automatic test exclusions.

This is a direct-source check, not transitive taint or redaction analysis.
Body values hidden in variables, computed arrays, arbitrary serialization calls or
helpers are outside initial coverage. Calling a redaction helper is accepted
because its result is unknown; this does not certify the helper as safe.
Selected values can still contain secrets and require an appropriate logging
contract. Correlation fields may be supplied by shared processors; this rule
does not require them at every call or verify the final JSON output.
Unknown unpacked selection arguments are not treated as proven full-input reads.
