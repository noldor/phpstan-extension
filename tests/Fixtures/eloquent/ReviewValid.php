<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat,Table};
use Illuminate\Database\Eloquent\Relations\{BelongsToMany,Relation};
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('review_list')]
class ListMapped extends BaseModel {}
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class OneLinePivot extends PivotOwner
{
    public function oneLine(): BelongsToMany { $relation = $this->belongsToMany(PivotRelated::class)->using(GoodPivot::class)->chaperone(declaring: 'owner', related: 'related'); return $relation; }
}
function reviewValidRegistrations(): void
{
    Relation::morphMap([ListMapped::class]);
    Relation::morphMap(['one_line' => OneLinePivot::class]);
}
