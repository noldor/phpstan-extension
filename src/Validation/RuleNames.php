<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PHPStan\Type\Type;
use Illuminate\Validation\Validator;
use PHPStan\Reflection\ReflectionProvider;

final class RuleNames
{
    private const array CONTROLS = ['bail', 'nullable', 'sometimes', 'exclude', 'exclude_if', 'exclude_unless', 'exclude_with', 'exclude_without'];

    private const array BUILDERS = [
        'Exists' => 'exists', 'Unique' => 'unique', 'In' => 'in', 'NotIn' => 'not_in',
        'Enum' => 'enum', 'ArrayRule' => 'array', 'StringRule' => 'string',
    ];

    public function __construct(
        private readonly ReflectionProvider $reflection,
    ) {}

    /**
     * @return list<string>
     */
    public function failingRules(Type $type, bool $split = true): array
    {
        $arrays = $type->getConstantArrays();

        if ($type->isConstantArray()->yes() && 1 === \count($arrays) && [] === $arrays[0]->getOptionalKeys()) {
            $rules = [];

            foreach ($arrays[0]->getValueTypes() as $value) {
                $rules = [...$rules, ...$this->failingRules($value, false)];
            }

            return \array_values(\array_unique($rules));
        }
        $strings = $type->getConstantStrings();

        if ($type->isString()->yes() && 1 === \count($strings)) {
            return $this->stringRules($strings[0]->getValue(), $split);
        }

        return $this->builderRules($type);
    }

    public function isBuiltin(string $name): bool
    {
        return null !== $this->canonicalName($name);
    }

    private function canonicalName(string $name): string|null
    {
        if (! $this->reflection->hasClass(Validator::class)) {
            return null;
        }
        $name = match (\mb_strtolower($name)) {
            'int' => 'integer', 'bool' => 'boolean', default => $name,
        };
        $method = 'validate' . \str_replace(' ', '', \ucwords(\str_replace('_', ' ', $name)));
        $validator = $this->reflection->getClass(Validator::class);

        if (! $validator->hasNativeMethod($method)) {
            return null;
        }
        $suffix = \mb_substr($validator->getNativeMethod($method)->getName(), 8);

        return \mb_strtolower((string) \preg_replace('/(?<!^)[A-Z]/', '_$0', $suffix));
    }

    /**
     * @return list<string>
     */
    private function stringRules(string $definition, bool $split): array
    {
        $rules = [];

        foreach ($split ? \explode('|', $definition) : [$definition] as $rule) {
            $name = $this->canonicalName(\mb_trim(\explode(':', $rule, 2)[0]));

            if (null !== $name && ! \in_array($name, self::CONTROLS, true)) {
                $rules[] = $name;
            }
        }

        return \array_values(\array_unique($rules));
    }

    /**
     * @return list<string>
     */
    private function builderRules(Type $type): array
    {
        $classes = $type->getObjectClassReflections();

        if (! $type->isObject()->yes() || 1 !== \count($classes)) {
            return [];
        }

        foreach ($classes as $class) {
            foreach (self::BUILDERS as $builder => $rule) {
                if (0 === \strcasecmp($class->getName(), 'Illuminate\\Validation\\Rules\\' . $builder)) {
                    return [$rule];
                }
            }
        }

        // Application rule objects and closures own their public messages.
        return [];
    }
}
