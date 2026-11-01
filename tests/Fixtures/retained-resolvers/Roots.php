<?php

declare(strict_types=1);

namespace Retained\Roots;

use Illuminate\Http\Request;
use Retained\Services as S;

class Direct // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly Request $request) {}
}
class Indirect // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\IndirectService $service) {}
}
class ParentProperty // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\ChildState $state) {}
}
class TraitConstructor // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\TraitService $service) {}
}
class InheritedConstructor // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\InheritedService $service) {}
}
class FirstCycle // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\CycleA $service) {}
}
class SecondCycle // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\CycleB $service) {}
}
class BoundInterface // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\Bound $service) {}
}
class MappedInterface // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\Mapped $service) {}
}
class Unresolved // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\Unknown $service) {}
}
class FactoryMapping // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\MappedFactory $service) {}
}
class ReviewedUnsafeFactory // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\UnsafeFactory $service) {}
}
class Scoped // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\ScopedContract $service) {}
}
class AliasBinding // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\AliasContract $service) {}
}
class ArrayState // @expect lighthouse.retainedUnsafeDependency
{
    /** @var list<S\UnsafeService> */
    private array $services;
}
class MixedState // @expect lighthouse.retainedUnknownDependency
{
    private mixed $state;
}
class UnionState // @expect lighthouse.retainedUnsafeDependency
{
    private Request|S\SafeService|null $state;
}
class IntersectionState // @expect lighthouse.retainedUnknownDependency
{
    private S\Bound&S\Unknown $state;
}
class RequiredScalar // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly string $name) {}
}
class ClosureState // @expect lighthouse.retainedUnknownDependency
{
    /** @var \Closure(Request): void */
    private \Closure $callback;
}
class AttributeBinding // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\AttributeBound $service) {}
}
class ParameterAttribute // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\AttributedParameter $service) {}
}
class Contextual // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\ContextualContract $service) {}
}
class ProviderProperty // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\PropertyBound $service) {}
}
class BoundarySubclass // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\NativeSubclass $service) {}
}
class Excluded
{
    public function __construct(private readonly Request $request) {}
}
class ExclusionIsNotTerminal // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly Excluded $service) {}
}
class Safe
{
    public function __construct(private readonly S\SafeService $service, private readonly S\Values $values) {}
    public function __invoke(Request $request): string { return $this->service->execute($request); }
}
class Reviewed
{
    public function __construct(private readonly S\ReviewedFactory $service) {}
}
class Boundary
{
    public function __construct(private readonly S\NativeContract $service) {}
}
class SafeCycle
{
    public function __construct(private readonly S\SafeCycleA $service) {}
}
class BindingCycle // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\BindingCycleA $service) {}
}
class Nullable // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\NullableService $service) {}
}
class IteratorState // @expect lighthouse.retainedUnsafeDependency
{
    /** @var \ArrayIterator<int, S\UnsafeService> */
    private \ArrayIterator $state;
}
class ReviewedScalar
{
    public function __construct(private readonly S\ReviewedScalarFactory $service) {}
}
class ContextRepository // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly \Illuminate\Log\Context\Repository $context) {}
}
class Actor // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly \Illuminate\Contracts\Auth\Authenticatable $actor) {}
}
class LoadedModel // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly \Illuminate\Database\Eloquent\Model $model) {}
}
class QueryBuilder // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly \Illuminate\Database\Query\Builder $query) {}
}
class RecursiveIterable
{
    public function __construct(private readonly S\RecursiveValues $values) {}
}
class SafeTerminalAlternatives // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\SafeBase $service) {}
}
class UnpackedConcrete // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\UnpackedBase $service) {}
}
class GenericState // @expect lighthouse.retainedUnsafeDependency
{
    /** @var S\Box<S\UnsafeChild> */
    private S\Box $state;
}
class GenericRepository
{
    /** @var S\StatelessRepository<\Illuminate\Database\Eloquent\Model> */
    private S\StatelessRepository $repository;
}
class MultiEntryBindingCycle // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\BindingCycleA $a, private readonly S\BindingCycleB $b) {}
}
class PrivateConstruction // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\PrivateConstructor $service) {}
}
class PlainIterable // @expect lighthouse.retainedUnsafeDependency
{
    /** @var iterable<\Illuminate\Http\Request> */
    private iterable $state;
}
class FacadeRegistration // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\FacadeProduct $service) {}
}
class DirectContextual // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\ContextualProduct $service) {}
}
class OffsetRegistration // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\OffsetProduct $service) {}
}
class ArrayRegistration // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\ArrayProduct $service) {}
}
class BuilderRegistration // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\BuilderProduct $service) {}
}
class BuilderScopedRegistration // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\BuilderScopedProduct $service) {}
}
class ProviderSingletonList
{
    public function __construct(private readonly S\ProviderListProduct $service) {}
}
class GenericConstructor // @expect lighthouse.retainedUnsafeDependency
{
    /** @param S\Box<S\UnsafeChild> $box */
    public function __construct(private readonly S\Box $box) {}
}
class ExpandingGenericState // @expect lighthouse.retainedUnknownDependency
{
    /** @var S\ExpandingGeneric<int> */
    private S\ExpandingGeneric $state;
}
class GenericLiteralAlternatives // @expect lighthouse.retainedUnsafeDependency
{
    /** @var S\ConditionalBox<'safe'> */
    private S\ConditionalBox $a;
    /** @var S\ConditionalBox<'unsafe'> */
    private S\ConditionalBox $b;
}
class GenericSafeConditional
{
    /** @var S\ConditionalBox<'safe'> */
    private S\ConditionalBox $state;
}
class NullsafeRegistration // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\NullsafeBase $service) {}
}
class CoalesceRegistration // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\CoalesceProduct $service) {}
}
class NamedOffsetRegistration
{
    public function __construct(private readonly S\NamedOffsetProduct $service) {}
}
class NullMapRegistration
{
    public function __construct(private readonly S\NullMapProduct $service) {}
}
class InheritedHook // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\HookChild $service) {}
}
class AliasedExtender // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\AliasExtendProduct $service) {}
}
class AliasedHook // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\AliasHookProduct $service) {}
}
class ReviewedAlias
{
    public function __construct(private readonly S\AliasReviewedProduct $service) {}
}
class OrdinaryAliasBinding
{
    public function __construct(private readonly S\OrdinaryAliasProduct $service) {}
}
class SelfAlias // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\SelfAliasProduct $service) {}
}
class ContainerKeyAlias
{
    public function __construct(private readonly S\AliasKeyContract $service) {}
}

class ReviewedAliasInjection
{
    public function __construct(private readonly S\ReviewedAliasContract $service) {}
}
class ReviewedKeyInjection
{
    public function __construct(private readonly S\ReviewedKeyProduct $service) {}
}
class ReviewedUnsafeKeyInjection // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\ReviewedUnsafeKeyProduct $service) {}
}
class ReviewedAliasCycle // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\ReviewedCycleA $service) {}
}

class ReviewedProductDependencyOverlap // @expect lighthouse.retainedUnsafeDependency
{
    public function __construct(private readonly S\OverlapProduct $service) {}
}
class ReviewedNativeAliasOverlap // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\OverlapAliasA $service) {}
}
class HookBeforeAliasChild // @expect lighthouse.retainedUnknownDependency
{
    public function __construct(private readonly S\HookAliasChild $service) {}
}
class HookBeforeAliasCanonicalReview
{
    public function __construct(private readonly S\HookAliasBase $service) {}
}
