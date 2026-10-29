<?php
namespace EloquentFixture;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Relations\Relation;
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class ConstructorSource extends Model { protected function serializeDate(DateTimeInterface $date): string { return $date->format('Y-m-d\\TH:i:s.uP'); } public function __construct(array $attributes=[]) { parent::__construct($attributes); $this->setDateFormat('Y-m-d'); } }
trait AliasedDateConstructor { public function initializeDate() { parent::__construct(); $this->setDateFormat('Y-m-d'); } } class OtherConstructor { public function __construct() {} }
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class AliasedDateModel extends Model
{
    use AliasedDateConstructor { initializeDate as __construct; }
    protected function serializeDate(DateTimeInterface $date): string { return $date->format('Y-m-d\\TH:i:s.uP'); }
}
function registerConstructorSource(): void { Relation::morphMap(['constructor_source'=>ConstructorSource::class, 'aliased_date_model'=>AliasedDateModel::class]); }
