<?php
namespace PropertyDocsFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat, Table};
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\{HasMany, Relation};

/**
 * @property-read int $id
 * @property string $name
 */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('documented_models')]
class Complete extends \EloquentFixture\BaseModel
{
    /** @return Attribute<string, never> */
    protected function label(): Attribute { return Attribute::make(get: fn(): string => 'label'); }
    /** @return HasMany<Complete, $this> */
    public function children(): HasMany { return $this->hasMany(self::class); }
}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class Inherited extends \PropertyDocsVendor\DocumentedParent {}

/** @property-read int $id */
trait IdentifierDoc {}
/** @property string $name */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('documented_models')]
class TraitDocumentation extends \EloquentFixture\BaseModel { use IdentifierDoc; }

abstract class AbstractWithoutDocs extends \EloquentFixture\BaseModel {}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('documented_models')]
class Excluded extends \EloquentFixture\BaseModel {}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('documented_models')]
class DynamicTable extends \EloquentFixture\BaseModel
{
    /** @param array<string, mixed> $attributes */
    public function __construct(array $attributes = []) { parent::__construct($attributes); $this->setTable('tenant_models'); }
}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('documented_models')]
class DynamicConnection extends \EloquentFixture\BaseModel {}

/** @extends \PropertyDocsVendor\GenericParent<string> */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class GenericInherited extends \PropertyDocsVendor\GenericParent {}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('documented_models')]
class ConfiguredDynamicTable extends \EloquentFixture\BaseModel {}

/** @property int $name */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class RefinedVendorCast extends \PropertyDocsVendor\DocumentedParent
{
    protected function casts(): array { return ['name' => 'integer']; }
}

/** @property string $name */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('keyless_documented_models')]
class NoSyntheticIdentifier extends \EloquentFixture\BaseModel {}

function registerPropertyDocs(): void
{
    Relation::morphMap(['refined_vendor_cast' => RefinedVendorCast::class, 'documented_vendor_extension' => DocumentedVendorExtension::class, 'no_synthetic_identifier' => NoSyntheticIdentifier::class, 'generic_inherited' => GenericInherited::class, 'configured_dynamic_table' => ConfiguredDynamicTable::class, 'complete' => Complete::class, 'inherited' => Inherited::class,
        'trait_documentation' => TraitDocumentation::class, 'dynamic_table' => DynamicTable::class,
        'dynamic_connection' => DynamicConnection::class]);
}
