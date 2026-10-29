<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat,Table};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
/** @property-read string $label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class PublicModernInvalid extends BaseModel
{
    /** @return Attribute<string, never> */
    public function label(): Attribute { return Attribute::get(fn():string=>'x'); }
}
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class OverwrittenListModel extends BaseModel {}
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class LastListModel extends BaseModel {}
function secondReviewRegistrations(): void
{
    Relation::morphMap(['public_modern'=>PublicModernInvalid::class]);
    Relation::morphMap([OverwrittenListModel::class,LastListModel::class]);
}
