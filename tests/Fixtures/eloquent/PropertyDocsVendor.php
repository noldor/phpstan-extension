<?php
namespace PropertyDocsVendor;
use Illuminate\Database\Eloquent\Attributes\Table;
/**
 * @property-read int $id
 * @property string $name
 */
#[Table('documented_models')]
class DocumentedParent extends \EloquentFixture\BaseModel {}

#[Table('documented_models')]
class UndocumentedVendor extends \EloquentFixture\BaseModel {}

/**
 * @property int $id
 * @property string $name
 */
#[Table('documented_models')]
class WritableIdentifierParent extends \EloquentFixture\BaseModel {}

/**
 * @template TValue
 * @property-read int $id
 * @property TValue $name
 */
#[Table('documented_models')]
abstract class GenericParent extends \EloquentFixture\BaseModel {}
