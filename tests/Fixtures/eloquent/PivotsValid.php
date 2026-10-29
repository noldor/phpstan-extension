<?php

namespace EloquentFixture;

use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany, MorphPivot, MorphTo, MorphToMany, Pivot, Relation};

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class GoodPivot extends Pivot
{
    use JsonDates;
    /** @return BelongsTo<PivotOwner|BadPivotModel, $this> */
    public function owner(): BelongsTo { return $this->belongsTo(PivotOwner::class); }
    /** @return BelongsTo<PivotRelated, $this> */
    public function related(): BelongsTo { return $this->belongsTo(PivotRelated::class); }
}
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class PolymorphicPivot extends MorphPivot
{
    use JsonDates;
    /** @return MorphTo<PivotOwner, $this> */
    public function owner(): MorphTo { return $this->morphTo(); }
    /** @return BelongsTo<PivotRelated, $this> */
    public function related(): BelongsTo { return $this->belongsTo(PivotRelated::class); }
}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class PivotOwner extends BaseModel
{
    public function direct(): BelongsToMany { return $this->belongsToMany(PivotRelated::class)->using(GoodPivot::class)->as('membership')->chaperone(declaring: 'owner', related: 'related'); }
    public function variable(): BelongsToMany
    {
        $relation = $this->belongsToMany(PivotRelated::class, GoodPivot::class);
        $relation->chaperone(declaring: 'owner', related: 'related');
        return $relation;
    }
    public function morph(): MorphToMany { return $this->morphToMany(PivotRelated::class, 'subject')->using(PolymorphicPivot::class)->chaperone(declaring: 'owner', related: 'related'); }
}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class PivotRelated extends BaseModel
{
    public function inverse(): MorphToMany { return $this->morphedByMany(PivotOwner::class, 'subject')->using(PolymorphicPivot::class)->chaperone(declaring: 'related', related: 'owner'); }
}

function registerGoodPivots(): void { Relation::morphMap(['owner' => PivotOwner::class, 'related' => PivotRelated::class, 'pivot' => GoodPivot::class, 'morph_pivot' => PolymorphicPivot::class]); }
