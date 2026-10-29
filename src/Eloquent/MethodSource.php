<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Parser\Parser;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PHPStan\Reflection\ClassReflection;
use PhpParser\NodeVisitor\CloningVisitor;

final class MethodSource
{
    public function __construct(
        private readonly Parser $parser,
    ) {}

    public function find(ClassReflection $class, string $method): Node\Stmt\ClassMethod|null
    {
        $native = $class->getNativeReflection()->getMethod($method);
        $file = $native->getFileName();

        if (false === $file) {
            return null;
        }
        $nodes = new NodeTraverser(new CloningVisitor(), new NameResolver())->traverse($this->parser->parseFile($file));
        $owner = $class->getNativeMethod($method)->getDeclaringClass();
        $declarations = new NodeFinder()->findInstanceOf($nodes, Node\Stmt\ClassLike::class);
        $direct = new NodeFinder()->findFirst($declarations, static fn (Node $node): bool => $node instanceof Node\Stmt\ClassLike && null !== $node->namespacedName && 0 === \strcasecmp($node->namespacedName->toString(), $owner->getName()));
        $definitions = $this->matchingMethods($direct instanceof Node\Stmt\ClassLike ? $direct : null, [\mb_strtolower($method)], $native->getStartLine());

        if (1 === \count($definitions)) {
            return $definitions[0];
        }
        $sources = $this->sourceMethods($owner, $method);

        foreach ($declarations as $declaration) {
            $name = \mb_strtolower($declaration->namespacedName?->toString() ?? '');
            $definitions = [...$definitions, ...$this->matchingMethods($declaration, $sources[$name] ?? [], $native->getStartLine())];
        }

        return 1 === \count($definitions) ? $definitions[0] : null;
    }

    /** @param list<string> $names
     * @return list<Node\Stmt\ClassMethod>
     */
    private function matchingMethods(Node\Stmt\ClassLike|null $class, array $names, false|int $line): array
    {
        return \array_values(\array_filter($class?->getMethods() ?? [], static fn (Node\Stmt\ClassMethod $method): bool => $method->getStartLine() === $line && \in_array(\mb_strtolower($method->name->toString()), $names, true)));
    }

    /**
     * @return array<string, list<string>>
     */
    private function sourceMethods(ClassReflection $class, string $method): array
    {
        foreach ($class->getNativeReflection()->getTraitAliases() as $alias => $target) {
            if (0 !== \strcasecmp($alias, $method)) {
                continue;
            }
            [$name, $original] = \array_pad(\explode('::', $target, 2), 2, '');

            foreach ($class->getTraits(true) as $trait) {
                if (0 === \strcasecmp($trait->getName(), $name)) {
                    return $this->sourceMethods($trait, $original);
                }
            }

            return [];
        }
        $sources = [\mb_strtolower($class->getName()) => [\mb_strtolower($method)]];

        foreach ($class->getTraits() as $trait) {
            foreach ($this->sourceMethods($trait, $method) as $name => $methods) {
                $sources[$name] = [...($sources[$name] ?? []), ...$methods];
            }
        }

        return $sources;
    }
}
