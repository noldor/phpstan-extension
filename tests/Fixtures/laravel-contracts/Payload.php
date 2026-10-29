<?php

declare(strict_types=1);

namespace ContractFixtures\Models {
    class SoftRecord extends \Illuminate\Database\Eloquent\Model { use \Illuminate\Database\Eloquent\SoftDeletes; }
    class PhysicalRecord extends \Illuminate\Database\Eloquent\Model {}
    trait CustomLifecycle {}
    class CustomRecord extends \Illuminate\Database\Eloquent\Model { use CustomLifecycle; }
    trait WrappedLifecycle { use \Illuminate\Database\Eloquent\SoftDeletes; }
    abstract class WrappedBase extends \Illuminate\Database\Eloquent\Model { use WrappedLifecycle; }
    class InheritedSoftRecord extends WrappedBase {}
}

namespace ContractFixtures\Neutral {
    abstract class ModelPayload {
        public function __construct(private \ContractFixtures\Models\SoftRecord $record) {}
    }
    final readonly class ResultSnapshot { public function __construct(public int $id) {} }
}

namespace ContractFixtures\Jobs {
    final class ModelJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { // @expect laravel.queueRoute
        public function __construct(private \ContractFixtures\Models\SoftRecord $record) {} // @expect laravel.jobModelPayload
    }
    final class CollectionJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { // @expect laravel.queueRoute
        /** @var \Illuminate\Database\Eloquent\Collection<int, \ContractFixtures\Models\SoftRecord> */
        public \Illuminate\Database\Eloquent\Collection $records; // @expect laravel.jobModelPayload
    }
    final class ArrayJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { // @expect laravel.queueRoute
        /** @var array<string, list<\ContractFixtures\Models\SoftRecord>> */
        protected array $records; // @expect laravel.jobModelPayload
    }
    final class InheritedPayloadJob extends \ContractFixtures\Neutral\ModelPayload implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit {} // @expect laravel.jobModelPayload laravel.queueRoute
    final class ScalarJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { // @expect laravel.queueRoute
        public function __construct(private int $id, private \ContractFixtures\Neutral\ResultSnapshot $snapshot) {}
        public function handle(\ContractFixtures\Models\SoftRecord $local): void {}
    }
    final class AssignedPayloadJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { // @expect laravel.queueRoute
        private mixed $payload;
        public function __construct(\ContractFixtures\Models\SoftRecord $record) {
            $this->payload = $record; // @expect laravel.jobModelPayload
        }
    }
    final class ComputedPayloadJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { // @expect laravel.queueRoute
        public \ContractFixtures\Models\SoftRecord $record { get => new \ContractFixtures\Models\SoftRecord(); }
    }
    final class TransformingPayloadJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { // @expect laravel.queueRoute
        private int $id = 0;
        public mixed $payload { set { $this->id = 1; } }
        public function __construct(\ContractFixtures\Models\SoftRecord $record) { $this->payload = $record; }
    }
    final class UnknownPayloadJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { // @expect laravel.queueRoute
        public function __construct(private mixed $data) {}
    }
}
