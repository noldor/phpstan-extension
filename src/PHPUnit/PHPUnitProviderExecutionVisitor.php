<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\PHPUnit;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use Noldors\PHPStan\Analysis\ClosureExecution;

final class PHPUnitProviderExecutionVisitor extends NodeVisitorAbstract
{
    public const string METHOD_ATTRIBUTE = 'noldorsPHPUnitProviderMethod';

    /**
     * @param array<Node> $nodes
     */
    public function beforeTraverse(array $nodes): null
    {
        foreach ($nodes as $node) {
            $this->annotate($node, null, null);
        }

        return null;
    }

    private function annotate(Node $node, Node|null $parent, string|null $method): void
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            $method = $node->name->toString();
        } elseif ($node instanceof Node\Stmt\ClassLike || $node instanceof Node\FunctionLike && ! ClosureExecution::isImmediatelyInvoked($node, $parent)) {
            $method = null;
        }

        if ($node instanceof Node\Expr) {
            $node->setAttribute(self::METHOD_ATTRIBUTE, $method);
        }

        foreach ($this->children($node) as $child) {
            $this->annotate($child, $node, $method);
        }
    }

    /**
     * @return iterable<Node>
     */
    private function children(Node $node): iterable
    {
        foreach ($node->getSubNodeNames() as $name) {
            $value = $node->{$name};

            if ($value instanceof Node) {
                yield $value;
            } elseif (\is_array($value)) {
                foreach ($value as $child) {
                    if ($child instanceof Node) {
                        yield $child;
                    }
                }
            }
        }
    }
}
