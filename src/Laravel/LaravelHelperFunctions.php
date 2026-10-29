<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PHPStan\Reflection\FunctionReflection;

final class LaravelHelperFunctions
{
    /**
     * @var list<string>
     */
    private const array SERVICES = [
        '__', 'abort', 'abort_if', 'abort_unless', 'action', 'app', 'app_path', 'asset', 'auth', 'back', 'base_path', 'bcrypt',
        'broadcast', 'broadcast_if', 'broadcast_unless', 'cache', 'config', 'config_path', 'context', 'cookie', 'csrf_field',
        'csrf_token', 'database_path', 'decrypt', 'defer', 'dispatch', 'dispatch_sync', 'encrypt', 'env', 'event', 'fake',
        'info', 'lang_path', 'logger', 'logs', 'mix', 'old', 'policy', 'precognitive', 'public_path', 'redirect', 'report',
        'report_if', 'report_unless', 'request', 'rescue', 'resolve', 'resource_path', 'response', 'route', 'secure_asset',
        'secure_url', 'session', 'storage_path', 'to_action', 'to_route', 'trans', 'trans_choice', 'url', 'validator', 'view',
    ];

    /**
     * Additional global Laravel helpers enabled in all-helper mode.
     *
     * @var list<string>
     */
    private const array ADDITIONAL_HELPERS = [
        'append_config', 'blank', 'class_basename', 'class_uses_recursive', 'collect', 'data_fill', 'data_forget', 'data_get',
        'data_has', 'data_set', 'e', 'filled', 'fluent', 'head', 'laravel_cloud', 'last', 'literal', 'method_field', 'now',
        'lazy', 'object_get', 'once', 'optional', 'preg_replace_array', 'proxy', 'retry', 'str', 'tap', 'throw_if', 'throw_unless', 'today',
        'trait_uses_recursive', 'transform', 'uri', 'value', 'when', 'windows_os', 'with',
    ];

    /**
     * @param list<string> $helpers
     */
    public static function matches(FunctionReflection $function, bool $allHelpers, array $helpers): bool
    {
        $name = \mb_strtolower(\mb_ltrim($function->getName(), '\\'));

        if ($allHelpers && ! \str_contains($name, '\\') && self::isLaravelHelperSource($function->getFileName())) {
            return true;
        }
        $selected = [...self::SERVICES, ...($allHelpers ? self::ADDITIONAL_HELPERS : []), ...\array_map(static fn (string $helper): string => \mb_strtolower(\mb_ltrim($helper, '\\')), $helpers)];

        return \in_array($name, $selected, true);
    }

    private static function isLaravelHelperSource(string|null $file): bool
    {
        return null !== $file && 1 === \preg_match('~/(?:laravel/framework/src/Illuminate/(?:Collections|Support|Foundation|Reflection)|illuminate/(?:collections|support|foundation|reflection))/helpers\\.php$~', \str_replace('\\', '/', $file));
    }
}
