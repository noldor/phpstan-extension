<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Parser\Parser;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPStan\Reflection\ClassReflection;
use PhpParser\NodeVisitor\CloningVisitor;

final class ConstantSource
{
    public function __construct(
        private readonly Parser $parser,
    ) {}

    /**
     * @return array{expression: Node\Expr, owner: ClassReflection}|null
     */
    public function find(ClassReflection $class, string $name): array|null
    {
        if (! $class->hasConstant($name)) {
            return null;
        }
        $owner = $class->getConstant($name)->getDeclaringClass();
        $file = $owner->getFileName();

        if (null === $file) {
            return null;
        }
        $nodes = new NodeTraverser(new CloningVisitor(), new NameResolver())->traverse($this->parser->parseFile($file));
        $declaration = new NodeFinder()->findFirst($nodes, static fn (Node $node): bool => $node instanceof Node\Stmt\ClassLike && null !== $node->namespacedName && 0 === \strcasecmp($node->namespacedName->toString(), $owner->getName()));

        return $declaration instanceof Node\Stmt\ClassLike ? $this->inDeclaration($declaration, $name, $owner) : null;
    }

    /**
     * @return array{expression: Node\Expr, owner: ClassReflection}|null
     */
    private function inDeclaration(Node\Stmt\ClassLike $class, string $name, ClassReflection $owner): array|null
    {
        foreach ($class->stmts as $statement) {
            if (! $statement instanceof Node\Stmt\ClassConst) {
                continue;
            }

            foreach ($statement->consts as $constant) {
                if ($constant->name->toString() === $name) {
                    return ['expression' => $constant->value, 'owner' => $owner];
                }
            }
        }

        return null;
    }
}
