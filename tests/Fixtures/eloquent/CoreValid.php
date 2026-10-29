<?php

namespace EloquentFixture;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\{DateFormat, Table, Connection, Hidden, Fillable, WithoutTimestamps};

trait JsonDates
{
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d\\TH:i:s.uP');
    }
}

abstract class BaseModel extends Model
{
    use JsonDates;
}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[Table('good_models')]
#[Connection('pgsql')]
#[Hidden('password')]
#[Fillable('name')]
#[WithoutTimestamps]
class GoodModel extends BaseModel
{
    protected function casts(): array
    {
        return ['happened_at' => 'immutable_datetime:Y-m-d\\TH:i:s.uP'];
    }
}

#[DateFormat('Y-m-d\\TH:i:s.uP')]
class DynamicModel extends BaseModel
{
    protected $connection = 'pgsql';
    public function __construct(string $connection = 'pgsql')
    {
        parent::__construct();
        $this->connection = $connection;
    }
}

class Unselected extends Model {}

function registerCoreModels(): void
{
    \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
        'good' => GoodModel::class,
        'dynamic' => DynamicModel::class,
    ]);
}
