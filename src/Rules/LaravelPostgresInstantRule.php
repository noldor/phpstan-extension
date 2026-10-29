<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Rules\RuleErrorBuilder;
use Illuminate\Database\Schema\Blueprint;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Constant\ConstantIntegerType;

/**
 * @implements Rule<Node\Expr\MethodCall>
 */
final class LaravelPostgresInstantRule implements Rule
{
    /**
     * @param list<string> $paths
     * @param list<string> $excludePaths
     */
    public function __construct(
        private readonly FileHelper $fileHelper,
        private readonly array $paths,
        private readonly array $excludePaths,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr\MethodCall::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->isFirstClassCallable() || ! $this->isSelectedFile($scope->getFile())) {
            return [];
        }

        if (! new ObjectType(Blueprint::class)->isSuperTypeOf($scope->getType($node->var))->yes()) {
            return [];
        }

        foreach ($this->methodNames($node, $scope) as $method) {
            if ($this->isInvalidDeclaration($method, $node, $scope)) {
                return [RuleErrorBuilder::message('Use timestamp(6) with time zone for application-owned PostgreSQL instants; declare timestampTz(..., 6), timestampsTz(6) or softDeletesTz(..., 6).')
                    ->identifier('laravel.postgresqlInstant')
                    ->build()];
            }
        }

        return [];
    }

    private function isSelectedFile(string $file): bool
    {
        return $this->matchesPaths($file, $this->paths) && ! $this->matchesPaths($file, $this->excludePaths);
    }

    /**
     * @param list<string> $paths
     */
    private function matchesPaths(string $file, array $paths): bool
    {
        $file = $this->fileHelper->normalizePath($file, '/');

        foreach ($paths as $path) {
            $path = \mb_rtrim($this->fileHelper->normalizePath($this->fileHelper->absolutizePath($path), '/'), '/');

            if ($file === $path || \str_starts_with($file, $path . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function methodNames(Node\Expr\MethodCall $call, Scope $scope): array
    {
        if ($call->name instanceof Node\Identifier) {
            return [\mb_strtolower($call->name->toString())];
        }

        $type = $scope->getType($call->name);
        $names = $type->getConstantStrings();

        if ([] === $names || ! TypeCombinator::union(...$names)->isSuperTypeOf($type)->yes()) {
            return [];
        }

        return \array_map(static fn (ConstantStringType $name): string => \mb_strtolower($name->getValue()), $names);
    }

    private function isInvalidDeclaration(string $method, Node\Expr\MethodCall $call, Scope $scope): bool
    {
        if (\in_array($method, ['timestamp', 'datetime', 'timestamps', 'nullabletimestamps', 'datetimes', 'softdeletes', 'softdeletesdatetime'], true)) {
            return true;
        }

        if ('addcolumn' === $method) {
            return $this->isInvalidGenericColumn($call, $scope);
        }

        $position = match ($method) {
            'timestampstz', 'nullabletimestampstz' => 0,
            'timestamptz', 'datetimetz', 'softdeletestz' => 1,
            default => null,
        };

        return null !== $position && ! $this->isMicrosecondPrecision($this->argumentType($call, $scope, 'precision', $position));
    }

    private function isInvalidGenericColumn(Node\Expr\MethodCall $call, Scope $scope): bool
    {
        $type = $this->argumentType($call, $scope, 'type', 0);
        $parameters = $this->argumentType($call, $scope, 'parameters', 2);

        foreach ($this->parameterVariants($parameters) as $variant) {
            if ($this->isInvalidGenericDefinition($type, $variant)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<Type|null>
     */
    private function parameterVariants(Type|null $parameters): array
    {
        if (null === $parameters) {
            return [null];
        }

        $arrays = $parameters->getConstantArrays();

        return [] !== $arrays && TypeCombinator::union(...$arrays)->isSuperTypeOf($parameters)->yes() ? $arrays : [$parameters];
    }

    private function isInvalidGenericDefinition(Type|null $type, Type|null $parameters): bool
    {
        $precisionKey = new ConstantStringType('precision');
        $precision = null !== $parameters && $parameters->hasOffsetValueType($precisionKey)->yes()
            ? $parameters->getOffsetValueType($precisionKey)
            : null;

        foreach ($this->knownColumnTypes($type, $parameters) as $name) {
            $name = \mb_strtolower($name->getValue());

            if (\in_array($name, ['timestamp', 'datetime'], true)) {
                return true;
            }

            if (\in_array($name, ['timestamptz', 'datetimetz'], true) && ! $this->isMicrosecondPrecision($precision)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<ConstantStringType>
     */
    private function knownColumnTypes(Type|null $declaredType, Type|null $parameters): array
    {
        $types = $declaredType?->getConstantStrings() ?? [];
        $key = new ConstantStringType('type');

        if (null === $parameters || $parameters->hasOffsetValueType($key)->no()) {
            return $types;
        }

        $overrides = $parameters->getOffsetValueType($key)->getConstantStrings();

        return $parameters->hasOffsetValueType($key)->yes() ? $overrides : [...$types, ...$overrides];
    }

    private function isMicrosecondPrecision(Type|null $type): bool
    {
        return null !== $type && new ConstantIntegerType(6)->isSuperTypeOf($type)->yes();
    }

    private function argumentType(Node\Expr\MethodCall $call, Scope $scope, string $name, int $position): Type|null
    {
        foreach ($call->getArgs() as $argument) {
            if ($argument->name?->toString() === $name) {
                return $scope->getType($argument->value);
            }

            if ($argument->unpack) {
                $type = $scope->getType($argument->value);
                $key = new ConstantStringType($name);

                if ($type->hasOffsetValueType($key)->yes()) {
                    return $type->getOffsetValueType($key);
                }
            }
        }

        return $this->positionalArgumentType($call, $scope, $position);
    }

    private function positionalArgumentType(Node\Expr\MethodCall $call, Scope $scope, int $position): Type|null
    {
        foreach ($call->getArgs() as $argument) {
            if (null !== $argument->name) {
                continue;
            }

            $type = $scope->getType($argument->value);
            $types = $argument->unpack ? $this->unpackedPositionalTypes($type) : [$type];

            if (null === $types) {
                return null;
            }

            if (isset($types[$position])) {
                return $types[$position];
            }

            $position -= \count($types);
        }

        return null;
    }

    /**
     * @return list<Type>|null
     */
    private function unpackedPositionalTypes(Type $type): array|null
    {
        $arrays = $type->getConstantArrays();

        if (\count($arrays) !== 1 || [] !== $arrays[0]->getOptionalKeys() || ! $arrays[0]->isSuperTypeOf($type)->yes()) {
            return null;
        }

        $types = [];

        foreach ($arrays[0]->getKeyTypes() as $index => $key) {
            if ($key instanceof ConstantIntegerType) {
                $types[] = $arrays[0]->getValueTypes()[$index];
            }
        }

        return $types;
    }
}
