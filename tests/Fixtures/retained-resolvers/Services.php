<?php

declare(strict_types=1);

namespace Retained\Services;

use Illuminate\Http\Request;

class SafeService implements MappedFactory, ReviewedFactory, ScopedContract
{
    public function execute(Request $request): string { return $request->path(); }
}
class UnsafeService implements Bound, Mapped, UnsafeFactory, AliasContract, PropertyBound
{
    public function __construct(private readonly Request $request) {}
}
class IndirectService
{
    public function __construct(private readonly UnsafeService $service) {}
}
class ParentState
{
    private Request $request;
}
class ChildState extends ParentState {}
trait UnsafeConstructor
{
    public function __construct(private readonly Request $request) {}
}
class TraitService { use UnsafeConstructor; }
class InheritedService extends UnsafeService {}
class CycleA
{
    public function __construct(private readonly CycleB $b, private readonly Request $request) {}
}
class CycleB
{
    public function __construct(private readonly CycleA $a) {}
}
class SafeCycleA
{
    public function __construct(private readonly SafeCycleB $b) {}
}
class SafeCycleB
{
    public function __construct(private readonly SafeCycleA $a) {}
}
interface Bound {}
interface Unknown {}
interface Mapped {}
interface MappedFactory {}
interface ReviewedFactory {}
interface UnsafeFactory {}
interface NativeContract {}
interface AliasContract {}
interface ScopedContract {}
interface ContextualContract {}
interface PropertyBound {}
interface BindingCycleA {}
interface BindingCycleB {}
interface ReviewedScalarFactory {}
class ScalarProduct implements ReviewedScalarFactory
{
    public function __construct(private readonly string $endpoint) {}
}
class NullableService
{
    public function __construct(private readonly ?UnsafeService $service = null) {}
}
class UnsafeCollection extends \ArrayIterator
{
}
class NativeBoundary implements NativeContract
{
    public function __construct(private readonly \Illuminate\Container\Container $app) {}
}
class NativeSubclass extends NativeBoundary {}
class Values
{
    /** @var class-string<Request> */
    private string $requestClass = Request::class;
    /** @var list<string> */
    private array $names = [];
    public function __construct(private readonly string $endpoint = 'https://example.test') {}
}
#[\Illuminate\Container\Attributes\Bind(UnsafeService::class)]
interface AttributeBound {}
class AttributedParameter
{
    public function __construct(#[\Illuminate\Container\Attributes\Config('app.name')] private readonly string $name) {}
}
/** @implements \IteratorAggregate<int, self> */
class RecursiveValues implements \IteratorAggregate
{
    public function getIterator(): \Traversable { return new \EmptyIterator(); }
}
class SafeBase {}
class UnsafeChild extends SafeBase
{
    public function __construct(private readonly \Illuminate\Http\Request $request) {}
}
class UnpackedBase {}
class UnpackedChild extends UnpackedBase
{
    public function __construct(private readonly \Illuminate\Http\Request $request) {}
}
/** @template T of SafeBase */
class Box
{
    /** @var T */
    private SafeBase $value;
}
/** @template T of \Illuminate\Database\Eloquent\Model */
class StatelessRepository
{
    /** @var class-string<T> */
    private string $modelClass;
}
class PrivateConstructor
{
    private function __construct() {}
}
class FacadeProduct {}
class ContextualProduct {}
class OffsetProduct {}
class ArrayProduct {}
class BuilderProduct {}
class BuilderScopedProduct {}
class ProviderListProduct {}
/** @template T */
class ExpandingGeneric
{
    /** @var ExpandingGeneric<list<T>> */
    private ExpandingGeneric $next;
}
/** @template T of string */
class ConditionalBox
{
    /** @var (T is 'unsafe' ? \Illuminate\Http\Request : SafeService) */
    private object $state;
}
class NullsafeBase {}
class NullsafeChild extends NullsafeBase
{
    public function __construct(private readonly \Illuminate\Http\Request $request) {}
}
class CoalesceProduct {}
class NamedOffsetProduct {}
class NullMapProduct {}
class HookParent {}
class HookChild extends HookParent {}
class AliasExtendProduct {}
class AliasHookProduct {}
class AliasReviewedProduct {}
class OrdinaryAliasProduct {}
class SelfAliasProduct {}
interface AliasKeyContract {}
class AliasKeyProduct implements AliasKeyContract {}

interface ReviewedAliasContract {}
class ReviewedAliasProduct implements ReviewedAliasContract {}
class ReviewedKeyProduct {}
class ReviewedUnsafeKeyProduct { public function __construct(private readonly Request $request) {} }
interface ReviewedCycleA {}
interface ReviewedCycleB {}
class ReviewedCycleProduct implements ReviewedCycleA {}

class OverlapProduct { public function __construct(OverlapDependency $dependency) {} }
class OverlapDependency extends OverlapProduct { public function __construct() {} }
class OverlapUnsafe extends OverlapDependency { public function __construct(private readonly Request $request) {} }
class OverlapAliasA {}
class OverlapAliasB extends OverlapAliasA {}
class HookAliasBase {}
class HookAliasOther extends HookAliasBase {}
class HookAliasChild extends HookAliasBase {}
