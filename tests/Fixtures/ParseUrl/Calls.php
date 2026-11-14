<?php

declare(strict_types = 1);

namespace ParseUrlFixture\GlobalCalls {
    use Uri\Rfc3986\Uri;

    use function parse_url as legacyParser;

    $url = 'https://example.com/path';

    \parse_url($url);
    parse_url($url, \PHP_URL_HOST);
    \PARSE_URL($url);
    legacyParser($url);
    $parser = \parse_url(...);

    Uri::parse($url)?->getRawHost();
}

namespace ParseUrlFixture\LocalCalls {
    function parse_url(string $value): string
    {
        return $value;
    }

    parse_url('local');
    $parser = parse_url(...);
}
