<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\Laravel\RetainedBindingFacts;
use Noldors\PHPStan\Laravel\RetainedResolverPolicy;

/**
 * @phpstan-import-type BindingFact from RetainedBindingFacts
 *
 * @implements Collector<Node\Expr, list<BindingFact>>
 */
final class RetainedBindingCollector implements Collector
{
    public function __construct(
        private readonly RetainedResolverPolicy $policy,
        private readonly RetainedBindingFacts $facts,
        private readonly ReflectionProvider $reflection,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        if (! $node instanceof Node\Expr\MethodCall && ! $node instanceof Node\Expr\StaticCall && ! $node instanceof Node\Expr\Assign && ! $node instanceof Node\Expr\AssignOp\Coalesce) {
            return null;
        }

        if (! $this->policy->collectsBindings($scope->getFile())) {
            return null;
        }

        if ($node instanceof Node\Expr\Assign || $node instanceof Node\Expr\AssignOp\Coalesce) {
            return $this->arrayAssignment($node, $scope);
        }

        if ($node->isFirstClassCallable() || ! $this->registrationReceiver($node, $scope)) {
            return null;
        }

        if (! $node->name instanceof Node\Identifier) {
            return [['abstracts' => [], 'concretes' => [], 'kind' => 'opaque', 'line' => $node->getStartLine(), 'reason' => 'dynamic registration method']];
        }
        $method = \mb_strtolower($node->name->toString());

        if ($node instanceof Node\Expr\MethodCall && $this->receiverIs($node->var, $scope, \Illuminate\Foundation\Configuration\ApplicationBuilder::class)) {
            return $this->builderRegistration($node, $scope, $method);
        }

        return $this->containerRegistration($node, $scope, $method);
    }

    /**
     * @return list<BindingFact>|null
     */
    private function arrayAssignment(Node\Expr\Assign|Node\Expr\AssignOp\Coalesce $node, Scope $scope): array|null
    {
        if (! $node->var instanceof Node\Expr\ArrayDimFetch || ! $this->receiverIs($node->var->var, $scope, \Illuminate\Contracts\Container\Container::class)) {
            return null;
        }

        return [['abstracts' => $this->facts->strings(null !== $node->var->dim ? $scope->getType($node->var->dim) : null), 'concretes' => [], 'kind' => 'opaque', 'line' => $node->getStartLine(), 'reason' => 'container array registration']];
    }

    /**
     * @return list<BindingFact>|null
     */
    private function builderRegistration(Node\Expr\MethodCall $node, Scope $scope, string $method): array|null
    {
        $argument = match ($method) {
            'withbindings' => 'bindings',
            'withsingletons' => 'singletons',
            'withscopedsingletons' => 'scopedSingletons',
            default => null,
        };

        if (null === $argument) {
            return null;
        }

        return $this->facts->map($this->facts->argument($node, $scope, 0, $argument), $node->getStartLine(), 'withbindings' !== $method, 'withscopedsingletons' === $method ? 'scoped' : 'class');
    }

    /**
     * @return list<BindingFact>|null
     */
    private function containerRegistration(Node\Expr\MethodCall|Node\Expr\StaticCall $node, Scope $scope, string $method): array|null
    {
        $registrations = ['bind', 'bindif', 'singleton', 'singletonif', 'scoped', 'scopedif', 'instance', 'alias', 'when', 'extend', 'resolving', 'beforeresolving', 'afterresolving', 'rebinding', 'addcontextualbinding', 'offsetset', 'whenhasattribute', 'afterresolvingattribute'];

        if (! \in_array($method, $registrations, true)) {
            return null;
        }
        $abstractName = match ($method) {
            'alias' => 'alias',
            'when', 'addcontextualbinding' => 'concrete',
            'offsetset' => 'offset',
            default => 'abstract',
        };
        $abstracts = $this->facts->targets($this->facts->argument($node, $scope, 'alias' === $method ? 1 : 0, $abstractName));
        $concrete = $this->facts->argument($node, $scope, 'alias' === $method ? 0 : 1, 'alias' === $method ? 'abstract' : 'concrete');
        $concretes = $this->facts->strings($concrete);
        $unpacked = $this->facts->hasUnpack($node);
        $classBinding = \in_array($method, ['bind', 'bindif', 'singleton', 'singletonif', 'scoped', 'scopedif', 'alias'], true);

        if (! $unpacked && $classBinding && 'alias' !== $method && (null === $concrete || $concrete->isNull()->yes())) {
            $concretes = $abstracts;
        }
        $kind = ! $unpacked && $classBinding && [] !== $concretes ? 'class' : 'opaque';

        if (\in_array($method, ['scoped', 'scopedif'], true)) {
            $kind = 'scoped';
        }

        if (\in_array($method, ['whenhasattribute', 'afterresolvingattribute'], true)) {
            $abstracts = [];
        }

        if ('alias' === $method) {
            $kind = 'alias';

            if ($unpacked || [] === $concretes) {
                $abstracts = [];
            }
        }

        return [['abstracts' => $abstracts, 'concretes' => $concretes, 'kind' => $kind, 'line' => $node->getStartLine(), 'reason' => 'container ' . $method . ' registration', 'inherited' => \in_array($method, ['resolving', 'beforeresolving', 'afterresolving'], true), 'canonical' => \in_array($method, ['extend', 'rebinding', 'when', 'resolving', 'beforeresolving', 'afterresolving'], true)]];
    }

    private function registrationReceiver(Node\Expr\MethodCall|Node\Expr\StaticCall $node, Scope $scope): bool
    {
        if ($node instanceof Node\Expr\StaticCall) {
            if (! $node->class instanceof Node\Name) {
                return false;
            }
            $class = $scope->resolveName($node->class);

            return $this->reflection->hasClass($class) && $this->reflection->getClass($class)->is(\Illuminate\Support\Facades\App::class);
        }

        return $this->receiverIs($node->var, $scope, \Illuminate\Contracts\Container\Container::class) || $this->receiverIs($node->var, $scope, \Illuminate\Foundation\Configuration\ApplicationBuilder::class);
    }

    private function receiverIs(Node\Expr $receiver, Scope $scope, string $type): bool
    {
        foreach ($scope->getType($receiver)->getObjectClassReflections() as $class) {
            if ($class->is($type)) {
                return true;
            }
        }

        return false;
    }
}
