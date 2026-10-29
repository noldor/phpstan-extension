<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PhpParser\Node;
use PHPStan\Type\Type;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Reflection\ClassReflection;
use PhpParser\NodeVisitor\CloningVisitor;
use Noldors\PHPStan\Eloquent\MethodSource;
use PHPStan\Reflection\ReflectionProvider;

final class MethodArrays
{
    public function __construct(
        private readonly MethodSource $source,
        private readonly ConstantSource $constants,
        private readonly ReflectionProvider $reflection,
    ) {}

    /**
     * @return array<string, Type>|null
     */
    public function read(ClassReflection $class, string $method, Scope $scope): array|null
    {
        $expressions = $this->expressions($class, $method, $scope);

        return null === $expressions ? null : \array_map(static fn (Node\Expr $expression): Type => $scope->getType($expression), $expressions);
    }

    /**
     * @return array<string, Node\Expr>|null
     */
    public function expressions(ClassReflection $class, string $method, Scope $scope): array|null
    {
        if (! $class->hasNativeMethod($method)) {
            return [];
        }
        $declaration = $this->source->find($class, $method);

        if (null === $declaration) {
            return null;
        }
        $owner = $class->getNativeMethod($method)->getDeclaringClass()->getName();
        $expression = $this->returnedExpression($declaration, $owner);

        return null === $expression ? null : $this->expressionValues($expression, $scope);
    }

    /**
     * @return array<string, Type>|null
     */
    public function entries(Type $type): array|null
    {
        $arrays = $type->getConstantArrays();

        if (! $type->isConstantArray()->yes() || 1 !== \count($arrays) || [] !== $arrays[0]->getOptionalKeys()) {
            return null;
        }
        $entries = [];

        foreach ($arrays[0]->getKeyTypes() as $index => $key) {
            $strings = $key->getConstantStrings();

            if (1 !== \count($strings)) {
                return null;
            }
            $entries[$strings[0]->getValue()] = $arrays[0]->getValueTypes()[$index];
        }

        return $entries;
    }

    /**
     * @param list<string> $seen
     *
     * @return array<string, Node\Expr>|null
     */
    private function expressionValues(Node\Expr $expression, Scope $scope, array $seen = []): array|null
    {
        if ($expression instanceof Node\Expr\ClassConstFetch) {
            return $this->constantValues($expression, $scope, $seen);
        }

        if ($expression instanceof Node\Expr\BinaryOp\Plus) {
            $left = $this->expressionValues($expression->left, $scope, $seen);
            $right = $this->expressionValues($expression->right, $scope, $seen);

            return null === $left || null === $right ? null : $left + $right;
        }

        if (! $expression instanceof Node\Expr\Array_) {
            return null;
        }
        $entries = [];

        foreach ($expression->items as $item) {
            $itemEntries = $this->itemValues($item, $scope, $seen);

            if (null === $itemEntries) {
                return null;
            }
            $entries = \array_replace($entries, $itemEntries);
        }

        return $entries;
    }

    /**
     * @param list<string> $seen
     *
     * @return array<string, Node\Expr>|null
     */
    private function itemValues(Node\ArrayItem $item, Scope $scope, array $seen): array|null
    {
        if ($item->unpack) {
            return $this->expressionValues($item->value, $scope, $seen);
        }
        $keys = null === $item->key ? [] : $scope->getType($item->key)->toArrayKey()->getConstantStrings();

        return 1 === \count($keys) ? [$keys[0]->getValue() => $item->value] : null;
    }

    /**
     * @param list<string> $seen
     *
     * @return array<string, Node\Expr>|null
     */
    private function constantValues(Node\Expr\ClassConstFetch $expression, Scope $scope, array $seen): array|null
    {
        if (! $expression->class instanceof Node\Name || ! $expression->name instanceof Node\Identifier) {
            return null;
        }
        $classes = $scope->resolveTypeByName($expression->class)->getObjectClassReflections();

        if (1 !== \count($classes)) {
            return null;
        }
        $key = $classes[0]->getName() . '::' . $expression->name->toString();

        if (\in_array($key, $seen, true)) {
            return null;
        }
        $constant = $this->constants->find($classes[0], $expression->name->toString());

        return null === $constant ? null : $this->expressionValues($this->substitute($constant['expression'], [], $constant['owner']->getName()), $scope, [...$seen, $key]);
    }

    private function returnedExpression(Node\Stmt\ClassMethod $method, string $owner): Node\Expr|null
    {
        $variables = [];

        foreach ($method->stmts ?? [] as $statement) {
            if ($statement instanceof Node\Stmt\Nop) {
                continue;
            }

            if ($statement instanceof Node\Stmt\Return_) {
                return null === $statement->expr || $this->passesLocalsToCall($statement->expr, $variables) ? null : $this->substitute($statement->expr, $variables, $owner);
            }

            if (! $statement instanceof Node\Stmt\Expression || ! $statement->expr instanceof Node\Expr\Assign) {
                return null;
            }
            $assignment = $statement->expr;

            if (! $assignment->var instanceof Node\Expr\Variable || ! \is_string($assignment->var->name) || $this->passesLocalsToCall($assignment->expr, $variables)) {
                return null;
            }
            $variables[$assignment->var->name] = $this->substitute($assignment->expr, $variables, $owner);
        }

        return null;
    }

    /**
     * @param array<string, Node\Expr> $variables
     */
    private function passesLocalsToCall(Node\Expr $expression, array $variables): bool
    {
        $finder = new NodeFinder();

        return null !== $finder->findFirst($expression, static fn (Node $node): bool => $node instanceof Node\Expr\CallLike
            && null !== $finder->findFirst($node->getArgs(), static fn (Node $argument): bool => $argument instanceof Node\Expr\Variable && \is_string($argument->name) && isset($variables[$argument->name])));
    }

    /**
     * @param array<string, Node\Expr> $variables
     */
    private function substitute(Node\Expr $expression, array $variables, string $owner): Node\Expr
    {
        $parent = $this->reflection->getClass($owner)->getParentClass()?->getName();
        $visitor = new class ($variables, $owner, $parent) extends NodeVisitorAbstract {
            /**
             * @param array<string, Node\Expr> $variables
             */
            public function __construct(
                private readonly array $variables,
                private readonly string $owner,
                private readonly string|null $parent,
            ) {}

            public function leaveNode(Node $node): Node|null
            {
                if ($node instanceof Node\Expr\Variable && \is_string($node->name)) {
                    return $this->variables[$node->name] ?? null;
                }

                if ($node instanceof Node\Expr\ClassConstFetch && $node->class instanceof Node\Name) {
                    $owner = match (\mb_strtolower($node->class->toString())) {
                        'self' => $this->owner,
                        'parent' => $this->parent,
                        default => null,
                    };

                    if (null !== $owner) {
                        $node->class = new Node\Name\FullyQualified($owner);
                    }
                }

                return null;
            }
        };
        $nodes = new NodeTraverser(new CloningVisitor(), $visitor)->traverse([$expression]);
        \assert($nodes[0] instanceof Node\Expr);

        return $nodes[0];
    }
}
