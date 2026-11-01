<?php

declare(strict_types=1);

namespace Retained;

use Illuminate\Container\Container;
use Retained\Services as S;

function productionBindings(Container $app): void
{
    $app->bind(S\Bound::class, S\UnsafeService::class);
    $app->singleton(S\MappedFactory::class, static fn () => new S\SafeService());
    $app->bind(S\ReviewedFactory::class, static fn () => new S\SafeService());
    $app->bind(S\UnsafeFactory::class, static fn () => new S\UnsafeService(new \Illuminate\Http\Request()));
    $app->scoped(S\ScopedContract::class, S\SafeService::class);
    $app->alias(S\UnsafeService::class, S\AliasContract::class);
    $app->when(S\ContextualContract::class)->needs(S\SafeService::class)->give(S\SafeService::class);
    $app->alias(S\BindingCycleA::class, S\BindingCycleB::class);
    $app->alias(S\BindingCycleB::class, S\BindingCycleA::class);
    $app->bind(S\ReviewedScalarFactory::class, static fn () => new S\ScalarProduct('https://example.test'));
    $app->singleton(S\SafeService::class);
    $app->bind(S\SafeBase::class);
    $app->bind(S\SafeBase::class, S\UnsafeChild::class);
    $app->bind(S\UnpackedBase::class, ...[S\UnpackedChild::class]);
    \Illuminate\Support\Facades\App::singleton(S\FacadeProduct::class, static fn () => new S\FacadeProduct());
    $app->addContextualBinding(S\ContextualProduct::class, S\SafeService::class, static fn () => new S\SafeService());
    $app->offsetSet(S\OffsetProduct::class, static fn () => new S\OffsetProduct());
    $app[S\ArrayProduct::class] = static fn () => new S\ArrayProduct();
    $app[S\CoalesceProduct::class] ??= static fn () => new S\CoalesceProduct();
    $app->offsetSet(offset: S\NamedOffsetProduct::class, value: static fn () => new S\NamedOffsetProduct());
    $app->resolving(S\HookParent::class, static fn () => null);
    $app->alias(S\AliasExtendProduct::class, 'extend.first');
    $app->alias('extend.first', 'extend.second');
    $app->extend('extend.second', static fn () => new S\AliasExtendProduct());
    $app->alias(S\AliasHookProduct::class, 'hook.alias');
    $app->afterResolving('hook.alias', static fn () => null);
    $app->alias(S\AliasReviewedProduct::class, 'reviewed.alias');
    $app->extend('reviewed.alias', static fn () => new S\AliasReviewedProduct());
    $app->alias(S\OrdinaryAliasProduct::class, 'ordinary.alias');
    $app->bind('ordinary.alias', S\UnsafeChild::class);
    $app->alias(S\SelfAliasProduct::class, S\SelfAliasProduct::class);
    $app->singleton('retained.key', S\AliasKeyProduct::class);
    $app->alias('retained.key', S\AliasKeyContract::class);
}
class Provider extends \Illuminate\Support\ServiceProvider
{
    public $bindings = [S\PropertyBound::class => S\UnsafeService::class, S\NullMapProduct::class => null];
    public $singletons = [S\ProviderListProduct::class];
}
function builderBindings(\Illuminate\Foundation\Configuration\ApplicationBuilder $builder): void
{
    $builder->withBindings([S\BuilderProduct::class => static fn () => new S\BuilderProduct()]);
    $builder->withScopedSingletons([S\BuilderScopedProduct::class]);
    $builder->withBindings([S\NullMapProduct::class => null]);
}
function nullableBindings(?Container $app): void
{
    $app?->bind(S\NullsafeBase::class, S\NullsafeChild::class);
}

function reviewedAliases(Container $app): void
{
    $app->alias(S\ReviewedAliasProduct::class, 'reviewed.middle');
    $app->alias('reviewed.middle', S\ReviewedAliasContract::class);
    $app->extend(S\ReviewedAliasContract::class, static fn (S\ReviewedAliasProduct $old) => $old);
    $app->resolving('reviewed.middle', static fn () => null);
    $app->singleton('reviewed.client', static fn () => new S\ReviewedKeyProduct());
    $app->alias('reviewed.client', S\ReviewedKeyProduct::class);
    $app->singleton('reviewed.unsafe.client', static fn () => new S\ReviewedUnsafeKeyProduct(new \Illuminate\Http\Request()));
    $app->alias('reviewed.unsafe.client', S\ReviewedUnsafeKeyProduct::class);
    $app->alias(S\ReviewedCycleA::class, S\ReviewedCycleB::class);
    $app->alias(S\ReviewedCycleB::class, S\ReviewedCycleA::class);
}

function overlappingProductPaths(Container $app): void
{
    $app->bind(S\OverlapProduct::class, static fn () => new S\OverlapProduct(new S\OverlapDependency()));
    $app->bind(S\OverlapDependency::class, S\OverlapUnsafe::class);
    $app->bind(S\OverlapAliasA::class, static fn () => new S\OverlapAliasB());
    $app->alias(S\OverlapAliasA::class, S\OverlapAliasB::class);
    $app->alias(S\OverlapAliasB::class, S\OverlapAliasA::class);
    $app->resolving(S\HookAliasBase::class, static fn () => null);
    $app->alias(S\HookAliasOther::class, S\HookAliasBase::class);
}
