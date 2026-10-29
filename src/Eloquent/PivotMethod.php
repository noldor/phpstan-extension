<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PhpParser\Node;
use PHPStan\Analyser\Scope;

final class PivotMethod
{
    public function __construct(
        private readonly PivotChain $chain,
    ) {}

    /**
     * @param array<Node\Stmt> $statements
     */
    public function returnedState(array $statements, Node\Stmt\Return_ $return, Scope $scope): PivotState|null
    {
        $variables = [];

        foreach ($statements as $statement) {
            if ($statement->getStartFilePos() >= $return->getStartFilePos()) {
                break;
            }

            if ($statement instanceof Node\Stmt\Expression) {
                $variables = $this->applyExpression($statement->expr, $scope, $variables);
            } elseif (! $statement instanceof Node\Stmt\Nop && ! $statement instanceof Node\Stmt\Return_) {
                // A control-flow block may mutate an existing relation. Do not guess its final state.
                $variables = [];
            }
        }

        return $this->chain->resolve($return->expr, $scope, $variables);
    }

    /**
     * @param array<string, PivotState|null> $variables
     *
     * @return array<string, PivotState|null>
     */
    private function applyExpression(Node\Expr $expression, Scope $scope, array $variables): array
    {
        $assigned = null;

        if ($expression instanceof Node\Expr\Assign && $expression->var instanceof Node\Expr\Variable && \is_string($expression->var->name)) {
            $assigned = $expression->var->name;
            $expression = $expression->expr;
        }
        $name = $this->chain->variable($expression);
        $next = $this->chain->resolve($expression, $scope, $variables);

        if (null !== $name && isset($variables[$name])) {
            $previous = $variables[$name];

            foreach ($variables as $alias => $state) {
                if ($state === $previous) {
                    $variables[$alias] = $next;
                }
            }
        }

        if (null !== $assigned) {
            $variables[$assigned] = $next;
        }

        return $variables;
    }
}
