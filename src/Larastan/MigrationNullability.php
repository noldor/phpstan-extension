<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Larastan;

use PhpParser\Node;
use PhpParser\NodeFinder;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use Larastan\Larastan\Properties\MigrationHelper;

final class MigrationNullability
{
    /**
     * @var list<string>|null
     */
    private readonly array|null $uncertainColumns;

    public function __construct(MigrationHelper $migrations, Parser $parser, bool $disableMigrationScan)
    {
        $this->uncertainColumns = $disableMigrationScan ? [] : $this->uncertainColumns($migrations, $parser);
    }

    public function isReliable(string $name): bool
    {
        return null !== $this->uncertainColumns && ! \in_array($name, $this->uncertainColumns, true);
    }

    /**
     * @return list<string>|null
     */
    private function uncertainColumns(MigrationHelper $migrations, Parser $parser): array|null
    {
        $columns = [];

        foreach ($migrations->getMigrationFiles() as $file) {
            try {
                $nodes = $parser->parseFile($file->getPathname());
            } catch (ParserErrorsException) {
                return null;
            }

            $fileColumns = $this->fileColumns($nodes);

            if (null === $fileColumns) {
                return null;
            }
            $columns = [...$columns, ...$fileColumns];
        }

        return $columns;
    }

    /** @param array<Node> $nodes
     * @return list<string>|null
     */
    private function fileColumns(array $nodes): array|null
    {
        $columns = [];

        foreach (new NodeFinder()->findInstanceOf($nodes, Node\Expr\MethodCall::class) as $call) {
            if (! $call->name instanceof Node\Identifier) {
                return null;
            }
            $method = \mb_strtolower($call->name->toString());

            if ('addcolumn' === $method) {
                return null;
            }

            if (! \in_array($method, ['timestamptz', 'timetz', 'year'], true)) {
                continue;
            }
            $name = $this->columnName($call);

            if (null === $name) {
                return null;
            }
            $columns[] = $name;
        }

        return $columns;
    }

    private function columnName(Node\Expr\MethodCall $call): string|null
    {
        foreach ($call->getArgs() as $index => $arg) {
            if ('column' === $arg->name?->toString() || (0 === $index && null === $arg->name)) {
                return $arg->value instanceof Node\Scalar\String_ ? $arg->value->value : null;
            }
        }

        return null;
    }
}
