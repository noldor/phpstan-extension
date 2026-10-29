<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PhpParser\Node;
use PHPStan\Reflection\ClassReflection;
use Illuminate\Database\Eloquent\Casts\Attribute;

final class AccessorShape
{
    public function __construct(
        private readonly MethodSource $methods,
    ) {}

    public function getterOnly(ClassReflection $class, string $method): bool|null
    {
        $factory = $this->factoryForMethod($class, $method);

        if (null === $factory) {
            return null;
        }
        $getter = $this->hasCallback($factory, 'get');
        $setter = $this->hasCallback($factory, 'set');

        if (false === $getter || true === $setter) {
            return false;
        }

        return null === $getter || null === $setter ? null : true;
    }

    public function hasGetter(ClassReflection $class, string $method): bool|null
    {
        $factory = $this->factoryForMethod($class, $method);

        return null === $factory ? null : $this->hasCallback($factory, 'get');
    }

    public function factory(Node\Expr|null $expression): Node\Expr\StaticCall|null
    {
        while ($expression instanceof Node\Expr\MethodCall && $expression->name instanceof Node\Identifier && \in_array($expression->name->toString(), ['shouldCache', 'withoutObjectCaching'], true)) {
            $expression = $expression->var;
        }

        return $expression instanceof Node\Expr\StaticCall && ! $expression->isFirstClassCallable() ? $expression : null;
    }

    public function factoryForMethod(ClassReflection $class, string $method): Node\Expr\StaticCall|null
    {
        $factory = $this->factory($this->returnExpression($class, $method));

        return null !== $factory && $factory->class instanceof Node\Name && Attribute::class === $factory->class->toString() && $factory->name instanceof Node\Identifier ? $factory : null;
    }

    private function returnExpression(ClassReflection $class, string $method): Node\Expr|null
    {
        $definition = $this->methods->find($class, $method);
        $statements = $definition->stmts ?? [];

        if (1 === \count($statements) && $statements[0] instanceof Node\Stmt\Return_) {
            return $statements[0]->expr;
        }

        if (2 !== \count($statements) || ! $statements[0] instanceof Node\Stmt\Expression || ! $statements[0]->expr instanceof Node\Expr\Assign || ! $statements[1] instanceof Node\Stmt\Return_) {
            return null;
        }
        $assigned = $statements[0]->expr->var;
        $returned = $statements[1]->expr;

        if (! $assigned instanceof Node\Expr\Variable || ! \is_string($assigned->name) || ! $returned instanceof Node\Expr\Variable || $assigned->name !== $returned->name) {
            return null;
        }

        return $statements[0]->expr->expr;
    }

    private function hasCallback(Node\Expr\StaticCall $factory, string $name): bool|null
    {
        if (! $factory->name instanceof Node\Identifier) {
            return null;
        }
        $method = \mb_strtolower($factory->name->toString());

        if ('make' !== $method) {
            return \in_array($method, ['get', 'set'], true) ? $method === $name : null;
        }

        foreach ($factory->getArgs() as $index => $argument) {
            if ($argument->unpack) {
                return null;
            }

            if ($name === ($argument->name?->toString() ?? (0 === $index ? 'get' : 'set'))) {
                return $this->callbackPresence($argument->value);
            }
        }

        return false;
    }

    private function callbackPresence(Node\Expr $expression): bool|null
    {
        if ($expression instanceof Node\Expr\ConstFetch && 'null' === $expression->name->toLowerString()) {
            return false;
        }

        return $expression instanceof Node\Expr\Closure || $expression instanceof Node\Expr\ArrowFunction ? true : null;
    }
}
