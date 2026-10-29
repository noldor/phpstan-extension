<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;

final class DateCastCheck
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        private readonly string $jsonDateFormat,
        private readonly PropertyExceptions $dateCastExceptions,
    ) {}

    public function isVerified(Type $type, ClassReflection $class): bool
    {
        $arrays = $type->getConstantArrays();

        return [] !== $arrays && TypeCombinator::union(...$arrays)->equals($type) && [] === $this->invalidProperties($type, $class);
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(Type $type, ClassReflection $class): array
    {
        return \array_map(static fn (string $name) => RuleErrorBuilder::message(\sprintf('Cast for $%s bypasses or cannot prove the configured JSON date format; use the required format or a verified exception.', $name))
            ->identifier('laravel.modelJsonDateFormat')->build(), $this->invalidProperties($type, $class));
    }

    /**
     * @return list<string>
     */
    public function invalidProperties(Type $type, ClassReflection $class): array
    {
        $invalid = [];

        foreach ($type->getConstantArrays() as $array) {
            foreach ($array->getKeyTypes() as $index => $key) {
                $name = Arguments::string($key);

                if (null !== $name && ! $this->dateCastExceptions->contains($class, $name) && $this->isInvalid(Arguments::string($array->getValueTypes()[$index]))) {
                    $invalid[] = $name;
                }
            }
        }

        return \array_values(\array_unique($invalid));
    }

    private function isInvalid(string|null $cast): bool
    {
        if (null === $cast) {
            return true;
        }
        $parts = \explode(':', $cast, 2);
        $name = $parts[0];
        $format = $parts[1] ?? null;

        if (\in_array($name, ['date', 'datetime', 'immutable_date', 'immutable_datetime', 'custom_datetime', 'immutable_custom_datetime'], true)) {
            return null !== $format && $format !== $this->jsonDateFormat;
        }

        return $this->reflectionProvider->hasClass($name) && $this->reflectionProvider->getClass($name)->is(SerializesCastableAttributes::class);
    }
}
