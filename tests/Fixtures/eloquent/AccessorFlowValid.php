<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat,Table};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\Relation;
/** @property string $database_label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class AssignedAttribute extends BaseModel
{
    /** @return Attribute<never, string> */
    protected function databaseLabel(): Attribute
    {
        $attribute = Attribute::set(fn(string $value):string=>trim($value));
        return $attribute;
    }
}
/** @property int $database_label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class LegacyReadModernWrite extends BaseModel
{
    public function getDatabaseLabelAttribute(): int { return 7; }
    /** @return Attribute<never, string> */
    protected function databaseLabel(): Attribute { return Attribute::set(fn(string $value):string=>$value); }
}
/** @property string $database_label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class IndirectAttribute extends BaseModel
{
    /** @return Attribute<never, string> */
    protected function databaseLabel(): Attribute { return $this->buildAttribute(); }
    /** @return Attribute<never, string> */
    private function buildAttribute(): Attribute { return Attribute::set(fn(string $value):string=>$value); }
}
function registerAccessorFlows(): void
{
    Relation::morphMap(['assigned_attribute'=>AssignedAttribute::class, 'legacy_read_modern_write'=>LegacyReadModernWrite::class, 'indirect_attribute'=>IndirectAttribute::class, 'modern_read_legacy_write'=>ModernReadLegacyWrite::class, 'constant_null_getter'=>ConstantNullGetter::class, 'assigned_getter'=>AssignedComputedGetter::class]);
}

/** @property string $label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class ModernReadLegacyWrite extends BaseModel
{
    public function setLabelAttribute(string $value): void { $this->attributes['database_label'] = $value; }
    /** @return Attribute<string, never> */
    protected function label(): Attribute { return Attribute::make(get: fn():string=>'label'); }
}

/** @property string $database_label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class ConstantNullGetter extends BaseModel
{
    private const NO_GETTER = null;
    /** @return Attribute<mixed, string> */
    protected function databaseLabel(): Attribute { return Attribute::make(get: self::NO_GETTER, set: fn(string $value):string=>$value); }
}

/** @property-read string $label */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class AssignedComputedGetter extends BaseModel
{
    /** @return Attribute<string, never> */
    protected function label(): Attribute { $attribute = Attribute::make(get: fn():string=>'label'); return $attribute; }
}
