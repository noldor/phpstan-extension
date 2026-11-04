<?php
namespace PropertyDocsFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat, Table};
use Illuminate\Database\Eloquent\Relations\Relation;

#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('documented_models')]
class NoComment extends \EloquentFixture\BaseModel {}

/** A model summary without property annotations. */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('documented_models')]
class SummaryOnly extends \EloquentFixture\BaseModel {}

/** @property-read int $id */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('documented_models')]
class Partial extends \EloquentFixture\BaseModel {}

/**
 * @property-read int $id
 * @property-write string $name
 */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('documented_models')]
class WriteOnly extends \EloquentFixture\BaseModel {}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('extended_documented_models')]
class ExtendedVendor extends \PropertyDocsVendor\DocumentedParent {}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class ChangedCast extends \PropertyDocsVendor\DocumentedParent
{
    protected function casts(): array { return ['name' => 'integer']; }
}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('unavailable_model_schema')]
class UnavailableSchema extends \EloquentFixture\BaseModel {}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class InheritedWritableIdentifier extends \PropertyDocsVendor\WritableIdentifierParent {}

function registerPropertyDocFailures(): void
{
    Relation::morphMap(['no_comment' => NoComment::class, 'summary_only' => SummaryOnly::class,
        'partial' => Partial::class, 'write_only' => WriteOnly::class, 'extended_vendor' => ExtendedVendor::class,
        'inherited_writable_identifier' => InheritedWritableIdentifier::class, 'changed_cast' => ChangedCast::class, 'unavailable_schema' => UnavailableSchema::class]);
}
