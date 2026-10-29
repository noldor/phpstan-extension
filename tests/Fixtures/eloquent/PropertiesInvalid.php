<?php

namespace EloquentFixture;

use Illuminate\Database\Eloquent\Attributes\{DateFormat, Table};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\{HasMany, Relation};

/**
 * @property int $id
 * @property int $name
 * @property string $label
 * @property string $count
 * @property Collection<int, GoodModel> $children
 * @property string $nmae
 */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class BadProperties extends BaseModel
{
    /** @return Attribute<string, string> */
    protected function label(): Attribute
    {
        return Attribute::get(fn (): string => 'label');
    }

    /** @return HasMany<BadProperties, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class);
    }
}

function registerBadProperties(): void
{
    Relation::morphMap(['bad_properties' => BadProperties::class]);
}
