<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\{DateFormat, Table};
use Illuminate\Database\Eloquent\Relations\Relation;
/** @property string $name */
#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('property_models')]
class WrongNullability extends BaseModel {}
function registerWrongNullability(): void { Relation::morphMap(['nullable' => WrongNullability::class]); }
