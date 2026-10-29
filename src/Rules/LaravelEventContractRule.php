<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Laravel\QueuePolicy;
use Noldors\PHPStan\Configuration\ClassScope;

/**
 * @implements Rule<InClassNode>
 */
final class LaravelEventContractRule implements Rule
{
    /**
     * @param list<string> $completedEventSuffixes
     */
    public function __construct(
        private readonly ClassScope $events,
        private readonly ClassScope $listeners,
        private readonly QueuePolicy $policy,
        private readonly PhpVersion $phpVersion,
        private readonly array $completedEventSuffixes,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $declaration = $node->getOriginalNode();

        if (! $declaration instanceof Node\Stmt\Class_) {
            return [];
        }

        if ($this->policy->isObserver($class)) {
            return $this->observerErrors($class);
        }

        return [...$this->eventErrors($class, $declaration), ...$this->listenerErrors($class, $declaration)];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function observerErrors(ClassReflection $class): array
    {
        if (! $class->is(\Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit::class) && ! $class->is(\Illuminate\Contracts\Queue\ShouldQueue::class) && ! $this->policy->defaultFlag($class)->toBoolean()->isTrue()->yes()) {
            return [];
        }

        return [RuleErrorBuilder::message('Keep model observers immediate: do not implement deferred/queued handler contracts or enable afterCommit.')->identifier('laravel.observerAfterCommit')->build()];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function eventErrors(ClassReflection $class, Node\Stmt\Class_ $declaration): array
    {
        if (! $this->events->contains($class)) {
            return [];
        }
        $errors = [];

        if ((! $class->isAbstract() && ! $declaration->isFinal()) || ($this->phpVersion->supportsReadOnlyClasses() && ! $class->isReadOnly())) {
            $errors[] = RuleErrorBuilder::message('Declare concrete events final readonly and abstract event bases abstract readonly.')->identifier('laravel.eventDeclaration')->build();
        }

        if (! $class->isAbstract() && $this->isCompletedEvent($declaration) && ! $class->is(\Illuminate\Contracts\Events\ShouldDispatchAfterCommit::class)) {
            $errors[] = RuleErrorBuilder::message('Completed lifecycle events must implement ShouldDispatchAfterCommit.')->identifier('laravel.eventAfterCommit')->build();
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function listenerErrors(ClassReflection $class, Node\Stmt\Class_ $declaration): array
    {
        if (! $this->listeners->contains($class) || $class->isAbstract()) {
            return [];
        }
        $errors = [];

        if (! $declaration->isFinal()) {
            $errors[] = RuleErrorBuilder::message('Declare concrete application listeners final.')->identifier('laravel.listenerFinal')->build();
        }

        if (! $class->is(\Illuminate\Contracts\Queue\ShouldQueue::class) && ! $class->is(\Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit::class)) {
            $errors[] = RuleErrorBuilder::message('Synchronous application listeners must implement ShouldHandleEventsAfterCommit.')->identifier('laravel.listenerAfterCommit')->build();
        }

        return $errors;
    }

    private function isCompletedEvent(Node\Stmt\Class_ $class): bool
    {
        $name = \mb_strtolower($class->name?->toString() ?? '');

        foreach ($this->completedEventSuffixes as $suffix) {
            if ('' !== $suffix && \str_ends_with($name, \mb_strtolower($suffix))) {
                return true;
            }
        }

        return false;
    }
}
