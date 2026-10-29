<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat,Table};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
/**
 * @property-read int $id
 * @property string $database_label
 * @property-read string $label
 */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class PublicAccessors extends BaseModel
{
    /** @return Attribute<string, string> */
    public function databaseLabel(): Attribute
    {
        return Attribute::make(get: fn():string=>'db',set: fn(string $value):string=>$value);
    }
    /** @return Attribute<string, never> */
    public function label(): Attribute { return Attribute::make(get: fn():string=>'x')->shouldCache(); }
}
function registerPublicAccessors(): void { Relation::morphMap(['public_accessors'=>PublicAccessors::class]); }
