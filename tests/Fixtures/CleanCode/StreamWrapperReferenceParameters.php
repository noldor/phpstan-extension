<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Tests\Fixtures\CleanCode;

final class NativeStreamWrapper
{
    public function stream_open(string $path, string $mode, int $options, string|null &$openedPath): bool
    {
        return true;
    }
}

final class CaseInsensitiveNativeStreamWrapper
{
    public function STREAM_OPEN(string $path, string $mode, int $options, string|null &$openedPath): bool
    {
        return true;
    }
}

final class AdditionalStreamWrapperReferences
{
    public function stream_open(string &$path, string &$mode, int &$options, string|null &$openedPath, int &$extra = 0): bool
    {
        return true;
    }

    public function replace(string &$value): void {}
}

final class StaticStreamOpen
{
    public static function stream_open(string $path, string $mode, int $options, string|null &$openedPath): bool
    {
        return true;
    }
}

final class NonPublicStreamOpen
{
    private function stream_open(string $path, string $mode, int $options, string|null &$openedPath): bool
    {
        return true;
    }
}

function stream_open(string $path, string $mode, int $options, string|null &$openedPath): bool
{
    return true;
}
