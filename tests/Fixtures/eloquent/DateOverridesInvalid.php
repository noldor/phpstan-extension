<?php
namespace EloquentFixture;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Relations\Relation;
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class StorageOverride extends BaseModel
{
    public function getDateFormat(): string { return 'Y-m-d'; }
}
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class ConstructorStorageOverride extends BaseModel
{
    public function __construct(array $attributes = []) { parent::__construct($attributes); $this->setDateFormat('Y-m-d'); }
}
abstract class VendorCastBase extends BaseModel
{
    protected function casts(): array { return ['instant' => 'datetime:Y-m-d']; }
}
#[DateFormat('Y-m-d\\TH:i:s.uP')]
class InheritedBadCast extends VendorCastBase {}
function registerDateOverrides(): void { Relation::morphMap(['storage_override'=>StorageOverride::class, 'constructor_override'=>ConstructorStorageOverride::class, 'inherited_cast'=>InheritedBadCast::class]); }
