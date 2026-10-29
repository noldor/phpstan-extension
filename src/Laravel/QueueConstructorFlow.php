<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use Closure;
use PhpParser\Node;

/**
 * @phpstan-type RouteFlow array{continuing: list<bool>, completed: list<bool>}
 */
final class QueueConstructorFlow
{
    /**
     * @param list<Node\Stmt> $statements
     * @param Closure(Node\Expr): (bool|null) $routeEffect
     */
    public function routes(array $statements, bool $initial, Closure $routeEffect): bool
    {
        $flow = $this->flow($statements, [$initial], $routeEffect);

        return ! \in_array(false, [...$flow['continuing'], ...$flow['completed']], true);
    }

    /**
     * @param list<Node\Stmt> $statements
     * @param list<bool> $continuing
     * @param Closure(Node\Expr): (bool|null) $routeEffect
     *
     * @return RouteFlow
     */
    private function flow(array $statements, array $continuing, Closure $routeEffect): array
    {
        $completed = [];

        foreach ($statements as $statement) {
            if ([] === $continuing) {
                break;
            }

            if ($statement instanceof Node\Stmt\Return_) {
                return ['continuing' => [], 'completed' => [...$completed, ...$continuing]];
            }

            if ($statement instanceof Node\Stmt\Expression && $statement->expr instanceof Node\Expr\Throw_) {
                return ['continuing' => [], 'completed' => $completed];
            }

            if ($statement instanceof Node\Stmt\If_) {
                $branch = $this->branches($statement, $continuing, $routeEffect);
                $continuing = $branch['continuing'];
                $completed = [...$completed, ...$branch['completed']];

                continue;
            }

            if ($statement instanceof Node\Stmt\Expression && null !== ($route = $routeEffect($statement->expr))) {
                $continuing = [$route];
            }
        }

        return ['continuing' => $continuing, 'completed' => $completed];
    }

    /**
     * @param list<bool> $incoming
     * @param Closure(Node\Expr): (bool|null) $routeEffect
     *
     * @return RouteFlow
     */
    private function branches(Node\Stmt\If_ $conditional, array $incoming, Closure $routeEffect): array
    {
        $branches = [$conditional, ...$conditional->elseifs];
        $continuing = null === $conditional->else ? $incoming : [];
        $completed = [];

        if (null !== $conditional->else) {
            $branches[] = $conditional->else;
        }

        foreach ($branches as $branch) {
            $flow = $this->flow(\array_values($branch->stmts), $incoming, $routeEffect);
            $continuing = [...$continuing, ...$flow['continuing']];
            $completed = [...$completed, ...$flow['completed']];
        }

        return ['continuing' => \array_values(\array_unique($continuing, \SORT_REGULAR)), 'completed' => \array_values(\array_unique($completed, \SORT_REGULAR))];
    }
}
