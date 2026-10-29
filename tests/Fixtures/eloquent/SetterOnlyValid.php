<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat, Table};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
/**
 * @property string $database_label
 * @property string $count
 * @property string|null $name
 */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class SetterOnlyProperties extends BaseModel
{
    /** @return Attribute<never, string> */
    public function databaseLabel(): Attribute { return Attribute::set(fn(string $value):string=>trim($value)); }
    /** @return Attribute<never, int> */
    protected function count(): Attribute { return Attribute::set(fn(int $value):int=>$value)->shouldCache(); }
    /** @return Attribute<mixed, string|null> */
    protected function name(): Attribute { return Attribute::make(set: fn(string|null $value):string|null=>$value); }
    protected function casts(): array { return ['count'=>'string']; }
}
function registerSetterOnlyProperties(): void { Relation::morphMap(['setter_only_properties'=>SetterOnlyProperties::class]); }
