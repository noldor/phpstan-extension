<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Assertions;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Noldors\PHPStan\Analysis\ClosureExecution;

final class AssertArgumentVisitor extends NodeVisitorAbstract
{
    public const string ATTRIBUTE = 'noldors.assertArgumentNames';

    public function afterTraverse(array $nodes): array|null
    {
        // NameResolver replaces imported callable names during traversal.
        // Store finalized names so aliases retain their actual function identity.
        foreach ($nodes as $node) {
            $this->annotate($node, null, []);
        }

        return null;
    }

    /**
     * @param list<Node\Name> $context
     */
    private function annotate(Node $node, Node|null $parent, array $context): void
    {
        $context = $this->contextFor($node, $parent, $context);

        if ($node instanceof Node\Expr) {
            $node->setAttribute(self::ATTRIBUTE, $context);
        }

        foreach ($this->children($node) as $child) {
            $this->annotate($child, $node, $context);
        }
    }

    /**
     * @param list<Node\Name> $context
     *
     * @return list<Node\Name>
     */
    private function contextFor(Node $node, Node|null $parent, array $context): array
    {
        if (($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike) && ! ClosureExecution::isImmediatelyInvoked($node, $parent)) {
            return [];
        }

        if ($node instanceof Node\Arg && $parent instanceof Node\Expr\FuncCall && $parent->name instanceof Node\Name && ! $parent->isFirstClassCallable()) {
            // Callable names are siblings of arguments, not ancestors: storing them
            // carries assertion context without creating cyclic parent references.
            $context[] = $parent->name;
        }

        return $context;
    }

    /**
     * @return iterable<Node>
     */
    private function children(Node $node): iterable
    {
        foreach ($node->getSubNodeNames() as $name) {
            $child = $node->{$name};

            foreach (\is_array($child) ? $child : [$child] as $item) {
                if ($item instanceof Node) {
                    yield $item;
                }
            }
        }
    }
}
