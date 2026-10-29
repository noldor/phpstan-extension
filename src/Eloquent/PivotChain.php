<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use Illuminate\Database\Eloquent\Relations\Pivot;

final class PivotChain
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    /**
     * @param array<string, PivotState|null> $variables
     */
    public function resolve(Node\Expr|null $expression, Scope $scope, array $variables): PivotState|null
    {
        if ($expression instanceof Node\Expr\Variable && \is_string($expression->name)) {
            return $variables[$expression->name] ?? null;
        }

        if (! $expression instanceof Node\Expr\MethodCall || ! $expression->name instanceof Node\Identifier || $expression->isFirstClassCallable()) {
            return null;
        }
        $method = \mb_strtolower($expression->name->toString());

        if (\in_array($method, ['belongstomany', 'morphtomany', 'morphedbymany'], true) && $expression->var instanceof Node\Expr\Variable && 'this' === $expression->var->name) {
            return $this->root($expression, $scope, 'belongstomany' !== $method);
        }
        $state = $this->resolve($expression->var, $scope, $variables);

        if (null === $state) {
            return null;
        }

        return $this->apply($state, $method, $expression, $scope);
    }

    public function variable(Node\Expr|null $expression): string|null
    {
        while ($expression instanceof Node\Expr\MethodCall) {
            $expression = $expression->var;
        }

        return $expression instanceof Node\Expr\Variable && \is_string($expression->name) ? $expression->name : null;
    }

    private function root(Node\Expr\MethodCall $call, Scope $scope, bool $morph): PivotState
    {
        $related = Arguments::string(Arguments::type($call->getArgs(), $scope, 'related', 0));
        $table = Arguments::string(Arguments::type($call->getArgs(), $scope, 'table', $morph ? 2 : 1));
        $pivot = null !== $table && $this->reflectionProvider->hasClass($table) && $this->reflectionProvider->getClass($table)->is(Pivot::class) ? $table : null;

        return new PivotState($related, $morph, $pivot);
    }

    private function apply(PivotState $state, string $method, Node\Expr\MethodCall $call, Scope $scope): PivotState|null
    {
        return match ($method) {
            'using' => $state->using(Arguments::string(Arguments::type($call->getArgs(), $scope, 'class', 0))),
            'chaperone' => $state->chaperone(
                Arguments::string(Arguments::type($call->getArgs(), $scope, 'declaring', 0)),
                Arguments::string(Arguments::type($call->getArgs(), $scope, 'related', 1)),
            ),
            'withoutchaperone' => $state->withoutChaperone(),
            'as', 'withpivot', 'withpivotvalue', 'withtimestamps', 'wherepivot', 'wherepivotin', 'wherepivotnotin', 'wherepivotnull', 'wherepivotnotnull', 'orderby', 'orderbypivot', 'where', 'with', 'select', 'addselect', 'withtrashed' => $state,
            default => null,
        };
    }
}
