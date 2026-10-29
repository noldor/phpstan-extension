<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PhpParser\Node;
use PhpParser\NodeFinder;
use Illuminate\Database\Eloquent\Model;
use PHPStan\Reflection\ClassReflection;

final class ModelConstructor
{
    public function __construct(
        private readonly MethodSource $methods,
    ) {}

    public function mutates(ClassReflection $class, string $property): bool
    {
        $constructor = $class->getNativeReflection()->getConstructor();

        if (null === $constructor || false === $constructor->getFileName() || Model::class === $constructor->getDeclaringClass()->getName()) {
            return false;
        }
        $definition = $this->methods->find($class, $constructor->getName());

        if (! $definition instanceof Node\Stmt\ClassMethod) {
            return true;
        }

        if (null !== new NodeFinder()->findFirst($definition->stmts ?? [], fn (Node $node): bool => $this->isMutation($node, $property))) {
            return true;
        }
        $delegates = new NodeFinder()->findFirst($definition->stmts ?? [], static fn (Node $node): bool => $node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name && 'parent' === $node->class->toLowerString() && $node->name instanceof Node\Identifier && '__construct' === $node->name->toLowerString());
        $parent = $class->getAncestorWithClassName($constructor->getDeclaringClass()->getName())?->getParentClass();

        return null !== $delegates && null !== $parent && $this->mutates($parent, $property);
    }

    private function isMutation(Node $node, string $property): bool
    {
        if ($node instanceof Node\Expr\Assign && $node->var instanceof Node\Expr\PropertyFetch) {
            return $node->var->var instanceof Node\Expr\Variable && 'this' === $node->var->var->name && $node->var->name instanceof Node\Identifier && $node->var->name->toString() === $property;
        }

        return $node instanceof Node\Expr\MethodCall && $node->var instanceof Node\Expr\Variable && 'this' === $node->var->name && $node->name instanceof Node\Identifier && 'set' . \mb_strtolower($property) === \mb_strtolower($node->name->toString());
    }
}
