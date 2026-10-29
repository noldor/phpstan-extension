<?php

namespace EloquentFixture;

use Illuminate\Database\Eloquent\Attributes\{DateFormat, Table};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\{HasMany, Relation};

trait HasChildren
{
    /** @return HasMany<GoodProperties, $this> */
    public function children(): HasMany { return $this->hasMany(GoodProperties::class); }
}

/**
 * @property-read int $id
 * @property string|null $name
 * @property int $count
 * @property array{enabled: bool} $options
 * @property string $database_label
 * @property \Carbon\CarbonImmutable $happened_at
 * @property-read string $label
 * @property-read int $rank
 * @property-read Collection<int, GoodProperties> $children
 */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class GoodProperties extends BaseModel
{
    use HasChildren;
    /** @return Attribute<string, never> */
    protected function label(): Attribute { return Attribute::make(get: fn (): string => 'label')->shouldCache(); }
    /** @return Attribute<string, never> */
    protected function databaseLabel(): Attribute { return Attribute::make(get: fn (): string => 'db'); }
    protected function casts(): array { return ['options' => 'array', 'happened_at' => 'immutable_datetime']; }
}

/** @property int $id */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class WritableKey extends BaseModel {}

function registerGoodProperties(): void
{
    Relation::morphMap(['properties' => GoodProperties::class, 'key' => WritableKey::class]);
}
