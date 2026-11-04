<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\ParserConfig;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Reflection\ClassReflection;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Ast\PhpDoc\PropertyTagValueNode;

final class PropertyDoc
{
    private readonly Lexer $lexer;

    private readonly PhpDocParser $parser;

    public function __construct()
    {
        $config = new ParserConfig(['lines' => true]);
        $constants = new ConstExprParser($config);
        $this->lexer = new Lexer($config);
        $this->parser = new PhpDocParser($config, new TypeParser($config, $constants), $constants);
    }

    /**
     * @return array{readable: Type|null, writable: bool}|null
     */
    public function property(ClassReflection $class, string $name): array|null
    {
        $tag = $class->getPropertyTags()[$name] ?? null;

        if (null !== $tag) {
            $readable = $tag->getReadableType();

            return [
                'readable' => null === $readable ? null : TypeTraverser::map($readable, static fn (Type $type, callable $traverse): Type => $type instanceof TemplateType
                    ? ($class->getActiveTemplateTypeMap()->getType($type->getName()) ?? $type)
                    : $traverse($type)),
                'writable' => $tag->isWritable(),
            ];
        }
        $parent = $class->getParentClass();
        $sources = [...$class->getTraits(), ...(null === $parent ? [] : [$parent]), ...$class->getInterfaces()];

        foreach ($sources as $source) {
            $property = $this->property($source, $name);

            if (null !== $property) {
                return $property;
            }
        }

        return null;
    }

    /**
     * @return list<array{name: string, line: int}>
     */
    public function entries(string $comment, int $startLine): array
    {
        $entries = [];
        $document = $this->parser->parse(new TokenIterator($this->lexer->tokenize($comment)));

        foreach ($document->getTags() as $tag) {
            if (! $tag->value instanceof PropertyTagValueNode) {
                continue;
            }
            $line = $tag->getAttribute('startLine');
            $entries[] = ['name' => \mb_ltrim($tag->value->propertyName, '$'), 'line' => $startLine + (\is_int($line) ? $line - 1 : 0)];
        }

        return $entries;
    }
}
