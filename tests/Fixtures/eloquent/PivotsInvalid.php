<?php

namespace EloquentFixture;

use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Relations\{BelongsToMany, Relation};

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class BadPivotModel extends BaseModel
{
    public function missing(): BelongsToMany { return $this->belongsToMany(PivotRelated::class); }
    public function before(): BelongsToMany { return $this->belongsToMany(PivotRelated::class)->chaperone(declaring: 'owner', related: 'related')->using(GoodPivot::class); }
    public function cleared(): BelongsToMany { return $this->belongsToMany(PivotRelated::class)->using(GoodPivot::class)->chaperone(declaring: 'owner', related: 'related')->withoutChaperone(); }
    public function unnamed(): BelongsToMany { return $this->belongsToMany(PivotRelated::class)->using(GoodPivot::class)->chaperone(); }
    public function unknown(): BelongsToMany { return $this->belongsToMany(PivotRelated::class)->using(GoodPivot::class)->chaperone(declaring: 'missing', related: 'related'); }
    public function wrongTarget(): BelongsToMany { return $this->belongsToMany(PivotRelated::class)->using(GoodPivot::class)->chaperone(declaring: 'related', related: 'owner'); }
}

function registerBadPivots(): void { Relation::morphMap(['bad_pivot' => BadPivotModel::class]); }
