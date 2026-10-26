<?php

declare(strict_types = 1);

namespace JsonThrowFixtures;

use const JSON_THROW_ON_ERROR as THROW_FLAG;

use function json_decode as decode;
use function json_encode as encode;

const ENCODING_FLAGS = \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT;

/**
 * @param list<mixed> $arguments
 */
function check(mixed $value, string $json, int $flags, bool $pretty, array $arguments, callable $callback): void
{
    json_encode($value);
    \JSON_ENCODE($value, 0);
    encode($value, \JSON_PRETTY_PRINT);
    \json_encode($value, flags: 0);
    \json_encode(value: $value, depth: 256);
    \json_decode($json);
    \json_decode($json, true);
    \json_decode($json, true, 512, \JSON_BIGINT_AS_STRING);
    \json_decode($json, flags: \JSON_OBJECT_AS_ARRAY);
    decode(json: $json, depth: 512, associative: true);
    \json_decode($json, true, \JSON_THROW_ON_ERROR);
    \json_encode($value, $flags);
    \json_decode($json, flags: $flags);
    \json_encode($value, $pretty ? \JSON_THROW_ON_ERROR : 0);
    \json_encode($value, ($flags | \JSON_THROW_ON_ERROR) & ~\JSON_THROW_ON_ERROR);
    $encoder = 'json_encode';
    $encoder($value);
    $decoder = '\\JSON_DECODE';
    $decoder($json, flags: 0);
    \json_encode(...$arguments);

    encode($value, \JSON_THROW_ON_ERROR);
    \json_encode($value, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_THROW_ON_ERROR);
    decode($json, true, 512, \JSON_BIGINT_AS_STRING | \JSON_THROW_ON_ERROR);
    \json_encode($value, THROW_FLAG);
    \json_encode(flags: \JSON_THROW_ON_ERROR, value: $value);
    \json_decode(depth: 512, flags: \JSON_THROW_ON_ERROR, json: $json, associative: true);
    \json_encode($value, ENCODING_FLAGS);
    \json_encode($value, $flags | \JSON_THROW_ON_ERROR);
    \json_decode($json, flags: \JSON_THROW_ON_ERROR | $flags);
    $knownFlags = $pretty ? \JSON_THROW_ON_ERROR : \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT;
    \json_encode($value, $knownFlags);
    \json_encode($value, \JSON_THROW_ON_ERROR | \JSON_PARTIAL_OUTPUT_ON_ERROR);
    $encoder($value, \JSON_THROW_ON_ERROR);
    $decoder($json, flags: \JSON_THROW_ON_ERROR);
    \json_encode(...$arguments, flags: \JSON_THROW_ON_ERROR);
    json_encode(...);
    json_decode(...);
    $callback($value);
}

namespace JsonThrowFixtures\Shadow;

function json_encode(mixed $value): string
{
    return 'custom';
}

function json_decode(string $value): string
{
    return $value;
}

function custom(mixed $value, string $json): void
{
    json_encode($value);
    json_decode($json);
    $encoder = __NAMESPACE__ . '\\json_encode';
    $encoder($value);
    \json_encode($value);
}
