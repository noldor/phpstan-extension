<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Laravel\QueuePolicy;
use Noldors\PHPStan\Configuration\ClassScope;
use Noldors\PHPStan\Laravel\PropertyLocation;

/**
 * @implements Rule<InClassNode>
 */
final class LaravelQueueContractRule implements Rule
{
    public function __construct(
        private readonly QueuePolicy $policy,
        private readonly ClassScope $listeners,
        private readonly ClassScope $notifications,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $declaration = $node->getOriginalNode();

        if (! $declaration instanceof Node\Stmt\Class_ || $class->isAbstract() || $this->policy->isObserver($class)) {
            return [];
        }
        $queuedRole = ($this->listeners->contains($class) || $this->notifications->contains($class)) && $class->is(\Illuminate\Contracts\Queue\ShouldQueue::class);

        if (! $this->policy->isJob($class) && ! $queuedRole) {
            return [];
        }
        $errors = [];

        if (! $class->is(\Illuminate\Contracts\Queue\ShouldQueueAfterCommit::class)) {
            $errors[] = RuleErrorBuilder::message('Application jobs and queued listeners/notifications must implement ShouldQueueAfterCommit.')->identifier('laravel.queueAfterCommit')->build();
        }

        if ($this->policy->isJob($class) && $this->policy->defaultFlag($class)->isFalse()->yes()) {
            $errors[] = RuleErrorBuilder::message('Do not disable the after-commit guarantee on an application job.')->identifier('laravel.queueBeforeCommit')->line(PropertyLocation::inClass($declaration, 'afterCommit'))->build();
        }

        return $errors;
    }
}
