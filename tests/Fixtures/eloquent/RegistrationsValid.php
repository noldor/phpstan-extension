<?php

namespace EloquentFixture;

use Illuminate\Database\Eloquent\Attributes\{DateFormat, ObservedBy};
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Migrations\Migration;

#[DateFormat('Y-m-d\\TH:i:s.uP')]
#[ObservedBy(FinalObserver::class)]
class AliasModel extends BaseModel {}
final class FinalObserver {}
class NotAnObserver {}

function validRegistrations(): void
{
    Relation::morphMap(['alias' => AliasModel::class]);
    AliasModel::observe(new FinalObserver);
    AliasModel::where('subject_type', Relation::getMorphAlias(AliasModel::class));
    $attributes = ['subject_type' => Relation::getMorphAlias(AliasModel::class)];
    $label = ['label' => 'alias'];
}

class HistoricalMigration extends Migration
{
    public function up(): void
    {
        AliasModel::where('subject_type', 'alias');
    }
}
