<?php

declare(strict_types=1);

namespace ContractFixtures\Operations;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use ContractFixtures\Models\SoftRecord;
use ContractFixtures\Models\PhysicalRecord;
use ContractFixtures\Models\CustomRecord;

final class LifecycleOperation
{
    /**
     * @param Builder<SoftRecord> $soft
     * @param Builder<PhysicalRecord> $physical
     * @param HasMany<SoftRecord, PhysicalRecord> $relation
     * @param Builder<CustomRecord> $custom
     */
    public function run(Builder $soft, Builder $physical, HasMany $relation, QueryBuilder $raw, SoftRecord $model, Builder $custom): void
    {
        $soft->delete(); // @expect laravel.bulkLifecycle
        $soft->restore(); // @expect laravel.bulkLifecycle
        $soft->forceDelete(); // @expect laravel.bulkLifecycle
        $soft->whereKey(1)->delete(); // @expect laravel.bulkLifecycle
        $soft?->delete(); // @expect laravel.bulkLifecycle
        $relation->delete(); // @expect laravel.bulkLifecycle
        $custom->delete(); // @expect laravel.bulkLifecycle
        SoftRecord::query()->whereKey(1)->delete(); // @expect laravel.bulkLifecycle
        \ContractFixtures\Models\InheritedSoftRecord::query()->delete(); // @expect laravel.bulkLifecycle
        $physical->delete();
        $raw->delete();
        $model->delete();
        $model->restore();
        $model->forceDelete();
        SoftRecord::destroy([1, 2]);
        SoftRecord::forceDestroy([1, 2]);
    }
}

final class ApprovedBulkOperation
{
    /** @param Builder<SoftRecord> $soft */
    public function run(Builder $soft): void { $soft->delete(); }
}
