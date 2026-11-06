<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PHPStan\Type\Type;
use PHPStan\Reflection\ReflectionProvider;

final class MessageClassNames
{
    public function __construct(
        private readonly ReflectionProvider $reflection,
    ) {}

    /**
     * @param array<string, Type>|null $messages
     *
     * @return list<string>
     */
    public function entries(array|null $messages): array
    {
        $invalid = [];

        foreach ($messages ?? [] as $key => $type) {
            if ($this->containsClass($key) || $this->typeContainsClass($type)) {
                $invalid[] = $key;
            }
        }

        return $invalid;
    }

    private function typeContainsClass(Type $type): bool
    {
        if ($type->isClassString()->yes()) {
            return true;
        }

        foreach ($type->getConstantStrings() as $string) {
            if ($this->containsClass($string->getValue())) {
                return true;
            }
        }

        foreach ($type->getConstantArrays() as $array) {
            foreach ([...$array->getKeyTypes(), ...$array->getValueTypes()] as $entry) {
                if ($this->typeContainsClass($entry)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function containsClass(string $text): bool
    {
        \preg_match_all('/[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*(?:\\\\[a-zA-Z_\\x80-\\xff][a-zA-Z0-9_\\x80-\\xff]*)*/', $text, $matches);

        foreach ($matches[0] as $name) {
            if ($this->reflection->hasClass($name) && (\str_contains($name, '\\') || $this->reflection->getClass($name)->getName() === $name)) {
                return true;
            }
        }

        return false;
    }
}
