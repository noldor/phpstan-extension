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
        'AcceptedIf', 'ArrayKeys', 'Can', 'Confirmed', 'Contains', 'CurrentPassword', 'DeclinedIf',
        'Dimensions', 'Distinct', 'DoesntContain', 'Email', 'Enum', 'Exists', 'In', 'InArray',
        'InArrayKeys', 'MissingIf', 'MissingUnless', 'MissingWith', 'MissingWithAll', 'NotIn', 'NotRegex',
        'PresentIf', 'PresentUnless', 'PresentWith', 'PresentWithAll', 'ProhibitedIfAccepted',
        'ProhibitedIfDeclined', 'Prohibits', 'Regex', 'RequiredArrayKeys', 'RequiredIfAccepted',
        'RequiredIfDeclined', 'RequiredWith', 'RequiredWithAll', 'RequiredWithout', 'RequiredWithoutAll',
        'Timezone', 'Unique', 'Url', 'Uuid', 'AnyOf', 'Numeric',
    ];

    private const array OBJECT_FAILURES = ['can', 'enum', 'any_of', 'password.mixed', 'password.letters', 'password.numbers', 'password.symbols', 'password.uncompromised'];

    private const array NATIVE_OBJECTS = [
        'Illuminate\\Validation\\Rules\\Can' => 'can', 'Illuminate\\Validation\\Rules\\Enum' => 'enum',
        'Illuminate\\Validation\\Rules\\AnyOf' => 'any_of', 'Illuminate\\Validation\\Rules\\File' => 'file',
        'Illuminate\\Validation\\Rules\\ImageFile' => 'image_file', 'Illuminate\\Validation\\Rules\\Email' => 'email',
        'Illuminate\\Validation\\Rules\\Password' => 'password',
    ];

    public function __construct(
        private readonly ReflectionProvider $reflection,
    ) {}

    /**
     * @return list<string>
     */
    public function failingRules(Type $type, bool $split = true): array
    {
        return \array_values(\array_filter($this->allRules($type, $split), $this->fails(...)));
    }

    public function fails(string $name): bool
    {
        return ! \in_array($name, self::CONTROLS, true);
    }

    /**
     * @return list<string>
     */
    public function allRules(Type $type, bool $split = true): array
    {
        $arrays = $type->getConstantArrays();

        if ($type->isConstantArray()->yes() && 1 === \count($arrays) && [] === $arrays[0]->getOptionalKeys()) {
            $rules = [];

            foreach ($arrays[0]->getValueTypes() as $value) {
                $rules = [...$rules, ...$this->allRules($value, false)];
            }

            return $rules;
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

    public function nativeAlias(string $class): string|null
    {
        return self::NATIVE_OBJECTS[$class] ?? null;
    }

    private function canonicalName(string $name): string|null
    {
        if ('' === $name) {
            return null;
        }

        if (isset(self::NATIVE_OBJECTS[$name])) {
            return $name;
        }

        if (\in_array(\mb_strtolower($name), [...self::OBJECT_FAILURES, ...self::CONTROLS], true)) {
            return \mb_strtolower($name);
        }

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

            if (null !== $name) {
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

        $class = $classes[0];

        if (isset(self::NATIVE_OBJECTS[$class->getName()])) {
            return [$class->getName()];
        }

        foreach (self::BUILDERS as $builder) {
            if (0 === \strcasecmp($class->getName(), 'Illuminate\\Validation\\Rules\\' . $builder)) {
                return [\mb_strtolower((string) \preg_replace('/(?<!^)[A-Z]/', '_$0', $builder))];
            }
        }

        return match ($class->getName()) {
            'Illuminate\\Validation\\Rules\\ArrayRule' => ['array'],
            'Illuminate\\Validation\\Rules\\StringRule' => ['string'],
            'Illuminate\\Validation\\Rules\\File', 'Illuminate\\Validation\\Rules\\ImageFile' => ['file'],
            // Application rule objects and closures own their public messages.
            default => [],
        };
    }
}
