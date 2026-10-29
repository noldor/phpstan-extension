<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Collectors\Collector;
use Noldors\PHPStan\Eloquent\Arguments;
use Noldors\PHPStan\Eloquent\ModelScope;
use Noldors\PHPStan\Eloquent\StaticModelTable;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * @phpstan-type Registration array{kind: string, classes: list<string>, models: list<string>, aliases: array<string, string>, line: int}
 *
 * @implements Collector<Node\Expr\CallLike, Registration>
 */
final class EloquentRegistrationCollector implements Collector
{
    public function __construct(
        private readonly ModelScope $models,
        private readonly StaticModelTable $tables,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr\CallLike::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        if (! $this->models->isEnabled()) {
            return null;
        }

        if (! $node instanceof Node\Expr\StaticCall && ! $node instanceof Node\Expr\MethodCall) {
            return null;
        }

        if ($node->isFirstClassCallable() || ! $node->name instanceof Node\Identifier) {
            return null;
        }
        $type = $node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name
            ? new ObjectType($scope->resolveName($node->class))
            : $scope->getType($node instanceof Node\Expr\StaticCall ? $node->class : $node->var);
        $method = \mb_strtolower($node->name->toString());

        if (\in_array($method, ['morphmap', 'enforcemorphmap'], true) && new ObjectType(Relation::class)->isSuperTypeOf($type)->yes()) {
            $map = Arguments::type($node->getArgs(), $scope, 'map', 0);

            if (null === $map || $map->isNull()->yes()) {
                return null;
            }
            $registration = $this->registration($map);

            return ['kind' => null === $registration ? 'unknownMap' : 'map', 'classes' => [], 'models' => $registration['models'] ?? [], 'aliases' => $registration['aliases'] ?? [], 'line' => $node->getStartLine()];
        }

        if ('observe' === $method && $this->isSelectedModelType($type)) {
            $argument = Arguments::type($node->getArgs(), $scope, 'classes', 0);

            return ['kind' => 'observers', 'classes' => [...Arguments::strings($argument), ...($argument?->getObjectClassNames() ?? [])], 'models' => [], 'aliases' => [], 'line' => $node->getStartLine()];
        }

        return null;
    }

    /**
     * @return array{models: list<string>, aliases: array<string, string>}|null
     */
    private function registration(Type $type): array|null
    {
        $arrays = $type->getConstantArrays();

        if (1 !== \count($arrays) || ! $arrays[0]->equals($type)) {
            return null;
        }

        if ($arrays[0]->isList()->yes()) {
            return $this->listRegistration($arrays[0]);
        }
        $map = [];

        foreach ($arrays[0]->getKeyTypes() as $index => $key) {
            $alias = Arguments::string($key);
            $class = Arguments::string($arrays[0]->getValueTypes()[$index]);

            if (null === $alias || null === $class || $arrays[0]->isOptionalKey($index)) {
                return null;
            }
            $map[$alias] = $class;
        }

        return ['models' => \array_values($map), 'aliases' => $map];
    }

    /**
     * @return array{models: list<string>, aliases: array<string, string>}|null
     */
    private function listRegistration(\PHPStan\Type\Constant\ConstantArrayType $array): array|null
    {
        $unresolvedModels = [];
        $aliases = [];

        foreach ($array->getValueTypes() as $index => $type) {
            $name = Arguments::string($type);

            if (null === $name || $array->isOptionalKey($index)) {
                return null;
            }
            $table = $this->tables->resolve($name);

            if (null !== $table) {
                $aliases[$table] = $name;
            } else {
                $unresolvedModels[] = $name;
            }
        }

        return ['models' => [...\array_values($aliases), ...$unresolvedModels], 'aliases' => $aliases];
    }

    private function isSelectedModelType(Type $type): bool
    {
        foreach ($type->getObjectClassReflections() as $class) {
            if ($this->models->contains($class)) {
                return true;
            }
        }

        return false;
    }
}
