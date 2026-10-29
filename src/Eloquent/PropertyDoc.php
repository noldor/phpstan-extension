<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\ParserConfig;
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
