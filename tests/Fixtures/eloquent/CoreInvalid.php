<?php

namespace EloquentFixture;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Relations\Relation;

#[DateFormat('Y-m-d')]
class BadConfiguration extends Model
{
    protected $casts = ['enabled' => 'bool'];
    protected $table = 'bad_models';
}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class BadCastFormat extends BaseModel
{
    protected function casts(): array
    {
        return ['happened_at' => 'datetime:Y-m-d'];
    }
}

function registerBadModels(): void
{
    Relation::morphMap(['bad' => BadConfiguration::class, 'cast' => BadCastFormat::class]);
}
