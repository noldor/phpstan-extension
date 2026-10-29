<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat,Table};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany,Relation};

/** @property-read string $label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class PublicLegacyAccessor extends BaseModel
{
    public function getLabelAttribute(): string { return 'x'; }
}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class AssignedPivotMutation extends PivotOwner
{
    public function changed(): BelongsToMany
    {
        $relation = $this->belongsToMany(PivotRelated::class)->using(GoodPivot::class)->chaperone(declaring: 'owner', related: 'related');
        $other = $relation->withoutChaperone();
        return $relation;
    }
}

abstract class ParentStorageMutation extends BaseModel
{
    public function __construct(array $attributes = []) { parent::__construct($attributes); $this->setDateFormat('Y-m-d'); }
}
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class DelegatingStorageMutation extends ParentStorageMutation
{
    public function __construct(array $attributes = []) { parent::__construct($attributes); }
}

#[Table(key: 'legacy_key')]
abstract class OldKeyBase extends BaseModel {}
/** @property int $id */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class ShadowedKey extends OldKeyBase {}

/** @property callable(int $argument): string $callback */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class CallbackProperty extends BaseModel
{
    /** @return Attribute<callable(int): string, never> */
    protected function callback(): Attribute { return Attribute::make(get: fn (): \Closure => static fn (int $argument): string => (string) $argument); }
}

#[Table(name: 'property_models', key: 'count')]
trait ChangesKey {}
/** @property int $count */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class TraitKey extends BaseModel { use ChangesKey; }

function registerReviewFailures(): void
{
    Relation::morphMap([
        'review_legacy'=>PublicLegacyAccessor::class, 'review_pivot'=>AssignedPivotMutation::class,
        'review_storage'=>DelegatingStorageMutation::class, 'review_key'=>ShadowedKey::class,
        'review_callback'=>CallbackProperty::class, 'review_trait_key'=>TraitKey::class,
    ]);
    PublicLegacyAccessor::where(value: 'review_legacy', operator: '=', column: 'subject_type');
}
