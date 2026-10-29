<?php

declare(strict_types=1);

namespace ContractFixtures\UseCases {
    class CreateOrderUseCase { private function handle(): void {} } // @expect architecture.useCaseFinal architecture.useCaseEntryPoint architecture.useCaseName
    final class MissingEntry {} // @expect architecture.useCaseEntryPoint
    abstract class BaseOperation { public function handle(): void {} }
    final class InheritedEntry extends BaseOperation {}
    class ExtensionPoint { public function handle(): void {} }
}

namespace ContractFixtures\Events {
    class MutableEvent {} // @expect laravel.eventDeclaration
    abstract class MutableBase {} // @expect laravel.eventDeclaration
    abstract readonly class ImmutableBase {}
    final readonly class OrdinaryEvent extends ImmutableBase {}
    final readonly class OrderCreated {} // @expect laravel.eventAfterCommit
    final readonly class OrderDeleted implements \Illuminate\Contracts\Events\ShouldDispatchAfterCommit {}
    /** @final */
    readonly class AnnotatedFinal {} // @expect laravel.eventDeclaration
}

namespace ContractFixtures\Listeners {
    final class ImmediateListener {} // @expect laravel.listenerAfterCommit
    class ExtensibleListener implements \Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit {} // @expect laravel.listenerFinal
    abstract class CommittedListener implements \Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit {}
    final class InheritedListener extends CommittedListener {}
    final class QueuedListener implements \Illuminate\Contracts\Queue\ShouldQueue {} // @expect laravel.queueAfterCommit
    final class CommittedQueuedListener implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit {}
    final class DynamicQueuedListener implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { public function shouldQueue(): bool { return false; } }
    final class ListenerObserver implements \Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit {} // @expect laravel.observerAfterCommit
}

namespace ContractFixtures\Observers {
    final class ImmediateObserver { public function creating(object $model): void {} }
    final class DeferredObserver implements \Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit {} // @expect laravel.observerAfterCommit
    final class QueuedObserver implements \Illuminate\Contracts\Queue\ShouldQueue {} // @expect laravel.observerAfterCommit
    final class PropertyDeferredObserver { public bool $afterCommit = true; } // @expect laravel.observerAfterCommit
    final class TruthyDeferredObserver { public int $afterCommit = 1; } // @expect laravel.observerAfterCommit
    final class HiddenFlagObserver { private bool $afterCommit = true; }
    final class FluentDeferredObserver {
        use \Illuminate\Bus\Queueable;
        public function prepare(): void { $this->afterCommit(); } // @expect laravel.observerAfterCommit
    }
    final class ConstructorDeferredObserver {
        public bool $afterCommit;
        public function __construct() {
            $this->afterCommit = true; // @expect laravel.observerAfterCommit
        }
    }
    final class InheritedQueueableObserver extends \ContractFixtures\Neutral\CompositeHandler {
        public function prepare(): void { $this->afterCommit(); } // @expect laravel.observerAfterCommit
    }
    final class CaseSensitiveObserver {
        public bool $afterCommit = false;
        public bool $AFTERCOMMIT = false;
        public function prepare(): void { $this->AFTERCOMMIT = true; }
    }
}

namespace ContractFixtures\Jobs {
    final class MissingQueueContract {} // @expect laravel.queueAfterCommit
    final class EarlyJob implements \Illuminate\Contracts\Queue\ShouldQueue {} // @expect laravel.queueAfterCommit
    abstract class CommittedJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit {}
    final class InheritedJob extends CommittedJob {}
    final class DefaultEarlyJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { public $afterCommit = false; } // @expect laravel.queueBeforeCommit
    final class PromotedEarlyJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit {
        public function __construct(public bool $afterCommit = false) {} // @expect laravel.queueBeforeCommit
    }
    final class LocalBeforeCommitJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit {
        use \Illuminate\Bus\Queueable;
        public function beforeCommit(): void {}
    }
    final class DispatchableJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit {
        use \Illuminate\Bus\Queueable;
        use \Illuminate\Foundation\Bus\Dispatchable;
        public function handle(): void {}
        public function other(): \Illuminate\Foundation\Bus\PendingDispatch { return \ContractFixtures\Neutral\UnselectedJob::dispatch(); }
    }
    abstract class CompositeJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit { use \Illuminate\Foundation\Queue\Queueable; }
    final class InheritedCompositeJob extends CompositeJob {}
    final class CaseSensitiveJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit {
        use \Illuminate\Bus\Queueable;
        public bool $AFTERCOMMIT = true;
    }
    final class UnevaluatedDefaultJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit {
        use \Illuminate\Bus\Queueable;
        public function __construct(public \DateTimeZone $zone = new \DateTimeZone('invalid/analysis-must-not-construct')) {}
    }
    final class CustomPendingJob implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit {
        use \Illuminate\Bus\Queueable;
        use \Illuminate\Foundation\Bus\Dispatchable;
        protected static function newPendingDispatch($job): \Illuminate\Foundation\Bus\PendingDispatch {
            return \ContractFixtures\Neutral\UnselectedJob::dispatch();
        }
    }
}

namespace ContractFixtures\Notifications {
    final class QueuedNotification extends \Illuminate\Notifications\Notification implements \Illuminate\Contracts\Queue\ShouldQueue {} // @expect laravel.queueAfterCommit
    final class CommittedNotification extends \Illuminate\Notifications\Notification implements \Illuminate\Contracts\Queue\ShouldQueueAfterCommit {}
    final class ImmediateNotification extends \Illuminate\Notifications\Notification {}
}

namespace ContractFixtures\Neutral {
    class OrdinaryService {}
    abstract class DeferredBase implements \Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit {}
    abstract class CompositeHandler { use \Illuminate\Foundation\Queue\Queueable; }
    final class UnselectedJob {
        use \Illuminate\Foundation\Bus\Dispatchable;
        use \Illuminate\Bus\Queueable;
    }
}

namespace ContractFixtures\Observers {
    final class InheritedDeferred extends \ContractFixtures\Neutral\DeferredBase {} // @expect laravel.observerAfterCommit
}

namespace ContractFixtures\UseCasesExtra { class NeighborUseCase {} }
