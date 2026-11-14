# RFC 3986 URI parsing

The PHP set reports the built-in `parse_url()` function with the
`uri.parseUrl` identifier. The check covers calls and first-class callables
that PHPStan resolves to that function, including imported aliases. Indirect
calls through strings or variables are outside its scope.

Use [`Uri\Rfc3986\Uri`](https://www.php.net/manual/en/class.uri-rfc3986-uri.php)
from PHP 8.5's URI extension. For example, read the host with a raw accessor:

```php
use Uri\Rfc3986\Uri;

$uri = Uri::parse($url);
if ($uri === null) {
    // Handle an invalid URI.
} else {
    $host = $uri->getRawHost();
}
```

`Uri::parse()` returns `null` for invalid input. Use the parsed object's
component accessors in place of array keys or `PHP_URL_*` arguments. Check
which inputs your application accepts, since RFC 3986 parsing can differ from
the legacy function.
