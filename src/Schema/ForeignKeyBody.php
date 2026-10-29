<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Schema;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Rules\IdentifierRuleError;
use Noldors\PHPStan\Eloquent\Arguments;
use Illuminate\Database\Schema\Blueprint;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use Illuminate\Database\Schema\ColumnDefinition;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use Illuminate\Database\Schema\ForeignIdColumnDefinition;

final class ForeignKeyBody
{
    /**
     * @var array<string, ForeignKeyBlueprint|ForeignKeyColumn|ForeignKeyDeclaration>
     */
    private array $objects = [];

    /**
     * @var array<string, Type>
     */
    private array $values = [];

    /**
     * @var list<ForeignKeyDeclaration>
     */
    private array $foreignKeys = [];

    public function __construct(
        private readonly Scope $scope,
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    /**
     * @return list<IdentifierRuleError>
     */
    public function errors(Node\FunctionLike $function): array
    {
        foreach ($function->getParams() as $parameter) {
            $type = $parameter->type;
            $type = $type instanceof Node\NullableType ? $type->type : $type;

            if ($type instanceof Node\Name && $parameter->var instanceof Node\Expr\Variable && \is_string($parameter->var->name)
                && new ObjectType(Blueprint::class)->isSuperTypeOf(new ObjectType($this->scope->resolveName($type)))->yes()) {
                $this->objects[$parameter->var->name] = new ForeignKeyBlueprint();
            }
        }

        foreach ($function->getStmts() ?? [] as $statement) {
            $this->statement($statement);
        }

        return \array_merge([], ...\array_map(static fn (ForeignKeyDeclaration $key): array => $key->errors(), $this->foreignKeys));
    }

    private function statement(Node $node, bool $conditional = false): void
    {
        if ($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike) {
            return;
        }

        if ($node instanceof Node\Expr) {
            $this->expression($node, $conditional);

            return;
        }

        $conditional = $conditional || ! $node instanceof Node\Stmt\Expression && ! $node instanceof Node\Stmt\Return_;

        $this->children($node, $conditional);
    }

    private function expression(Node\Expr $expression, bool $conditional): ForeignKeyBlueprint|ForeignKeyColumn|ForeignKeyDeclaration|null
    {
        if ($expression instanceof Node\Expr\Variable && \is_string($expression->name)) {
            return $this->objects[$expression->name] ?? null;
        }

        if ($expression instanceof Node\Expr\Assign) {
            return $this->assignment($expression, $conditional);
        }

        if ($expression instanceof Node\Expr\MethodCall || $expression instanceof Node\Expr\NullsafeMethodCall) {
            return $this->methodCall($expression, $conditional);
        }

        if ($expression instanceof Node\Expr\New_ && $expression->class instanceof Node\Name
            && new ObjectType(Blueprint::class)->isSuperTypeOf(new ObjectType($this->scope->resolveName($expression->class)))->yes()) {
            return new ForeignKeyBlueprint();
        }

        if (! $expression instanceof Node\FunctionLike) {
            $this->children($expression, $conditional);
        }

        return null;
    }

    private function assignment(Node\Expr\Assign $assignment, bool $conditional): ForeignKeyBlueprint|ForeignKeyColumn|ForeignKeyDeclaration|null
    {
        $object = $this->expression($assignment->expr, $conditional);
        $value = $this->value($assignment->expr);

        if (! $assignment->var instanceof Node\Expr\Variable || ! \is_string($assignment->var->name)) {
            return null;
        }

        unset($this->objects[$assignment->var->name], $this->values[$assignment->var->name]);

        if (null !== $object) {
            $this->objects[$assignment->var->name] = $object;
        } elseif (! $conditional) {
            $this->values[$assignment->var->name] = $value;
        }

        return $object;
    }

    private function methodCall(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $call, bool $conditional): ForeignKeyColumn|ForeignKeyDeclaration|null
    {
        if ($call->isFirstClassCallable()) {
            return null;
        }

        $object = $this->expression($call->var, $conditional);
        $name = $call->name instanceof Node\Identifier ? $call->name->toString() : Arguments::string($this->value($call->name));

        return null !== $name ? $this->call($object, \mb_strtolower($name), $call, $conditional) : null;
    }

    private function children(Node $node, bool $conditional): void
    {
        foreach ($node->getSubNodeNames() as $name) {
            $value = $node->{$name};

            foreach (\is_array($value) ? $value : [$value] as $child) {
                if ($child instanceof Node) {
                    $this->statement($child, $conditional);
                }
            }
        }
    }

    private function call(ForeignKeyBlueprint|ForeignKeyColumn|ForeignKeyDeclaration|null $object, string $method, Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $call, bool $conditional): ForeignKeyColumn|ForeignKeyDeclaration|null
    {
        if ($object instanceof ForeignKeyBlueprint) {
            return $this->blueprintCall($object, $method, $call, $conditional);
        }

        if ($object instanceof ForeignKeyColumn) {
            return $this->columnCall($object, $method, $call, $conditional);
        }

        if ($object instanceof ForeignKeyDeclaration) {
            $this->foreignKeyCall($object, $method, $call, $conditional);

            return $object;
        }

        return null;
    }

    private function blueprintCall(ForeignKeyBlueprint $blueprint, string $method, Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $call, bool $conditional): ForeignKeyColumn|ForeignKeyDeclaration|null
    {
        if ('foreign' === $method) {
            return $this->foreignKey($blueprint, Arguments::strings($this->argument($call, 'columns', 0)), $call->getStartLine());
        }

        if (! $this->isColumnMethod($method)) {
            return null;
        }

        $name = $this->columnName($method, $call);
        $nullable = $conditional ? null : $this->columnNullable($method, $call);
        $column = new ForeignKeyColumn($blueprint, $name, $this->isColumnMethod($method, ForeignIdColumnDefinition::class), $nullable);

        if (null !== $name) {
            $blueprint->columns[$name] = $column;
        }

        return $column;
    }

    private function columnName(string $method, Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $call): string|null
    {
        if ('addcolumn' === $method) {
            $name = $this->argument($call, 'name', 1);
            $parameters = $this->argument($call, 'parameters', 2);
            $key = new ConstantStringType('name');
            $name = null !== $parameters && $parameters->hasOffsetValueType($key)->yes() ? $parameters->getOffsetValueType($key) : $name;

            return Arguments::string($name);
        }

        $position = \in_array($method, ['foreignidfor', 'foreignuuidfor', 'foreignulidfor'], true) ? 1 : 0;
        $name = Arguments::string($this->argument($call, 'column', $position));

        return $name ?? match ($method) {
            'id' => 'id',
            'uuid' => 'uuid',
            'ulid' => 'ulid',
            'softdeletes', 'softdeletestz', 'softdeletesdatetime' => 'deleted_at',
            'remembertoken' => 'remember_token',
            default => null,
        };
    }

    private function columnNullable(string $method, Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $call): bool|null
    {
        if ('rawcolumn' === $method) {
            return null;
        }

        if ('addcolumn' === $method) {
            $parameters = $this->argument($call, 'parameters', 2);
            $key = new ConstantStringType('nullable');

            if (null !== $parameters && ! $parameters->hasOffsetValueType($key)->no()) {
                return $parameters->hasOffsetValueType($key)->yes() ? $this->boolean($parameters->getOffsetValueType($key)) : null;
            }
        }

        return \in_array($method, ['softdeletes', 'softdeletestz', 'softdeletesdatetime', 'remembertoken'], true);
    }

    private function boolean(Type $type): bool|null
    {
        return $type->isTrue()->yes() ? true : ($type->isFalse()->yes() ? false : null);
    }

    private function isColumnMethod(string $method, string $columnClass = ColumnDefinition::class): bool
    {
        if (! $this->reflectionProvider->hasClass(Blueprint::class)) {
            return false;
        }

        $class = $this->reflectionProvider->getClass(Blueprint::class);

        if (! $class->hasNativeMethod($method)) {
            return false;
        }

        foreach ($class->getNativeMethod($method)->getVariants() as $variant) {
            if (new ObjectType($columnClass)->isSuperTypeOf($variant->getReturnType())->yes()) {
                return true;
            }
        }

        return false;
    }

    private function columnCall(ForeignKeyColumn $column, string $method, Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $call, bool $conditional): ForeignKeyColumn|ForeignKeyDeclaration
    {
        if ('nullable' === $method) {
            $type = $this->argument($call, 'value', 0);
            $column->nullable = $conditional ? null : (null === $type || new ConstantBooleanType(true)->equals($type) ? true : (new ConstantBooleanType(false)->equals($type) ? false : null));
        }

        if ('table' === $method) {
            $column->explicitTable = $this->explicitString($this->argument($call, 'value', 0));
        }

        if ($column->supportsForeignKey && \in_array($method, ['constrained', 'references'], true)) {
            $key = $this->foreignKey($column->blueprint, null !== $column->name ? [$column->name] : [], $call->getStartLine(), $column);
            $key->explicitTable = 'constrained' === $method && ($column->explicitTable || $this->explicitString($this->argument($call, 'table', 0)));

            return $key;
        }

        return $column;
    }

    private function foreignKeyCall(ForeignKeyDeclaration $key, string $method, Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $call, bool $conditional): void
    {
        if ('on' === $method) {
            $key->explicitTable = $this->explicitString($this->argument($call, 'table', 0));
        }

        foreach (['delete', 'update'] as $action) {
            $explicit = $action . 'Action';
            $setNull = 'nullOn' . \ucfirst($action);

            if (\in_array($method, ['cascadeon' . $action, 'restricton' . $action, 'noactionon' . $action, 'nullon' . $action], true)) {
                $key->{$explicit} = true;
                $key->{$setNull} = ! $conditional && 'nullon' . $action === $method;
            } elseif ('on' . $action === $method) {
                $type = $this->argument($call, 'action', 0);
                $value = Arguments::string($type);
                $key->{$explicit} = $this->explicitString($type);
                $key->{$setNull} = ! $conditional && null !== $value && 'set null' === \mb_strtolower(\mb_trim($value));
            }
        }
    }

    /**
     * @param list<string> $columns
     */
    private function foreignKey(ForeignKeyBlueprint $blueprint, array $columns, int $line, ForeignKeyColumn|null $column = null): ForeignKeyDeclaration
    {
        $key = new ForeignKeyDeclaration($blueprint, $columns, $line, $column);
        $this->foreignKeys[] = $key;

        return $key;
    }

    private function explicitString(Type|null $type): bool
    {
        return null !== $type && $type->isString()->yes() && ! new ConstantStringType('')->equals($type);
    }

    private function argument(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall $call, string $name, int $position): Type|null
    {
        foreach ($call->getArgs() as $argument) {
            $type = $this->value($argument->value);

            if ($argument->name?->toString() === $name) {
                return $type;
            }

            if (null !== $argument->name) {
                continue;
            }

            if ($argument->unpack) {
                return $this->unpackedArgument($type, $name, $position);
            }

            if (0 === $position--) {
                return $type;
            }
        }

        return null;
    }

    private function unpackedArgument(Type $type, string $name, int $position): Type|null
    {
        $arrays = $type->getConstantArrays();

        if (1 !== \count($arrays) || [] !== $arrays[0]->getOptionalKeys()) {
            return null;
        }

        foreach ([new ConstantStringType($name), new ConstantIntegerType($position)] as $key) {
            if ($arrays[0]->hasOffsetValueType($key)->yes()) {
                return $arrays[0]->getOffsetValueType($key);
            }
        }

        return null;
    }

    private function value(Node\Expr $expression): Type
    {
        if ($expression instanceof Node\Expr\Array_) {
            $builder = ConstantArrayTypeBuilder::createEmpty();

            foreach ($expression->items as $item) {
                if ($item->unpack) {
                    return $this->scope->getType($expression);
                }

                $builder->setOffsetValueType(null !== $item->key ? $this->value($item->key) : null, $this->value($item->value));
            }

            return $builder->getArray();
        }

        return $expression instanceof Node\Expr\Variable && \is_string($expression->name)
            ? ($this->values[$expression->name] ?? $this->scope->getType($expression))
            : $this->scope->getType($expression);
    }
}
