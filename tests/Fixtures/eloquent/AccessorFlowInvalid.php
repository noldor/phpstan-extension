<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat,Table};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
trait SourceAccessor
{
    /** @return Attribute<string, never> */
    protected function databaseLabel(): Attribute { return Attribute::make(get: fn():string=>'label'); }
}
/** @property int $database_label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class TraitAccessorSource extends BaseModel { use SourceAccessor; }
function registerTraitAccessorSource(): void { Relation::morphMap(['trait_accessor_source'=>TraitAccessorSource::class]); }
