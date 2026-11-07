<?php

declare(strict_types = 1);

namespace GraphQLEnumDescriptions;

use GraphQL\Type\Definition\PhpEnumType as NativeEnum;
use Nuwave\Lighthouse\Schema\TypeRegistry;

final class OtherRegistry
{
    public function register(NativeEnum $type): void {}
}

/** @param class-string<\UnitEnum> $dynamicEnum */
function registerTypes(TypeRegistry $types, OtherRegistry $otherRegistry, string $dynamicEnum): void
{
    $types->register(new NativeEnum(DocumentedStatus::class, 'Documented'));
    $types->register(new NativeEnum(UndocumentedEnum::class, 'Undocumented', 'Constructor fallback.'));
    $types->register(new NativeEnum(UndocumentedEnum::class, 'SameEnumAgain'));

    $enum = UndocumentedCase::class;
    $types->overwrite(type: new NativeEnum(name: 'PartiallyDocumented', enumClass: $enum));

    new NativeEnum(InternalStatus::class, 'Internal');
    $otherRegistry->register(new NativeEnum(OtherRegistryStatus::class, 'OtherRegistry'));
    $types->registerLazy('LazyStatus', static fn (): NativeEnum => new NativeEnum(LazyStatus::class));
    $types->overwriteLazy(type: static function (): NativeEnum {
        return new NativeEnum(LazyReplacement::class);
    }, name: 'LazyReplacement');

    $dynamicFactory = static fn (): NativeEnum => new NativeEnum(DynamicLazyStatus::class);
    $types->registerLazy('DynamicLazyStatus', $dynamicFactory);
    $types->register(new NativeEnum($dynamicEnum));
}
