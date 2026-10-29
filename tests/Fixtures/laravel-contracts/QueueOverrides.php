<?php

declare(strict_types=1);

namespace ContractFixtures\Operations;

use ContractFixtures\Jobs\DispatchableJob;

final class DispatchOperation
{
    public function run(DispatchableJob $job, bool $flag, \ContractFixtures\Jobs\InheritedCompositeJob $inherited, \ContractFixtures\Jobs\CaseSensitiveJob $caseSensitive): void
    {
        $job->beforeCommit(); // @expect laravel.queueBeforeCommit
        $job->beforeCommit(...); // @expect laravel.queueBeforeCommit
        $job->afterCommit = false; // @expect laravel.queueBeforeCommit
        DispatchableJob::dispatch()->beforeCommit(); // @expect laravel.queueBeforeCommit
        DispatchableJob::dispatch()->onQueue('billing')->beforeCommit(); // @expect laravel.queueBeforeCommit
        dispatch(new DispatchableJob())->beforeCommit(); // @expect laravel.queueBeforeCommit
        $inherited->beforeCommit(); // @expect laravel.queueBeforeCommit
        \ContractFixtures\Jobs\InheritedCompositeJob::dispatch()->onQueue('billing')->beforeCommit(); // @expect laravel.queueBeforeCommit
        $caseSensitive->AFTERCOMMIT = false;
        $job->other()->beforeCommit();
        \ContractFixtures\Jobs\CustomPendingJob::dispatch()->beforeCommit();
        \ContractFixtures\Jobs\CustomPendingJob::dispatchIf(true)->beforeCommit();
        \ContractFixtures\Jobs\CustomPendingJob::dispatchUnless(false)->beforeCommit();
        new \ContractFixtures\Jobs\LocalBeforeCommitJob()->beforeCommit();
        $job->afterCommit = true;
        $job->afterCommit = $flag;
        $job->afterCommit();
        DispatchableJob::dispatch()->afterCommit();
        $pending = DispatchableJob::dispatch();
        $pending->beforeCommit();
    }
}

final class Unrelated
{
    public bool $afterCommit = false;
    public function beforeCommit(): void {}
    public function run(): void { $this->beforeCommit(); }
}
