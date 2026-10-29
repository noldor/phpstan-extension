<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat, Table};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
/** @property int $database_label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class WrongSetterOnlyType extends BaseModel
{
    /** @return Attribute<never, string> */
    public function databaseLabel(): Attribute { return Attribute::set(fn(string $value):string=>$value); }
}
/** @property-read string $database_label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class ThrowingGetter extends BaseModel
{
    /** @return Attribute<never, never> */
    protected function databaseLabel(): Attribute { return Attribute::get(fn():never=>throw new \LogicException('Unreadable')); }
}
function registerSetterOnlyErrors(): void
{
    Relation::morphMap(['wrong_setter_only'=>WrongSetterOnlyType::class, 'throwing_getter'=>ThrowingGetter::class]);
}
