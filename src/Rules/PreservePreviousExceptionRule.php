<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use Throwable;
use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;

/**
 * @implements Rule<Node\Stmt\Catch_>
 */
final class PreservePreviousExceptionRule implements Rule
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getNodeType(): string
    {
        return Node\Stmt\Catch_::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (null === $node->var || ! \is_string($node->var->name)) {
            return [];
        }

        $nodes = $this->nodesInCatch($node->stmts);
        $writes = $this->variableWrites($node->stmts, $scope);
        $caught = $node->var->name;

        if (isset($writes[$caught])) {
            return [];
        }

        $aliases = [$caught => $node->getStartFilePos()];
        $errors = [];

        foreach ($nodes as $current) {
            $aliases = $this->addStableAlias($current, $aliases, $writes);

            if (! $current instanceof Node\Expr\Throw_ || ! $current->expr instanceof Node\Expr\New_) {
                continue;
            }

            if ($this->preservesCause($current->expr, $scope, $aliases) !== false) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message('Preserve the caught exception as the previous exception when translating it.')
                ->identifier('exceptions.missingPrevious')->line($current->getStartLine())->build();
        }

        return $errors;
    }

    /**
     * @param array<Node> $nodes
     *
     * @return list<Node>
     */
    private function nodesInCatch(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            if ($node instanceof Node\FunctionLike || $node instanceof Node\Stmt\ClassLike || $node instanceof Node\Stmt\TryCatch) {
                continue;
            }

            $result[] = $node;
            \array_push($result, ...$this->nodesInCatch($this->childNodes($node)));
        }

        return $result;
    }

    /**
     * @param array<Node> $nodes
     *
     * @return array<string, int>
     */
    private function variableWrites(array $nodes, Scope $scope): array
    {
        $nodes = $this->nodesInScope($nodes);
        $writes = [];

        foreach ($nodes as $node) {
            foreach ($this->writtenVariables($node) as $name) {
                $writes[$name] = ($writes[$name] ?? 0) + 1;
            }
        }

        $assignments = $writes;

        foreach ($nodes as $node) {
            foreach ($this->referenceArguments($node, $scope, $assignments) as $name) {
                $writes[$name] = ($writes[$name] ?? 0) + 1;
            }
        }

        return $writes;
    }

    /**
     * @param array<Node> $nodes
     *
     * @return list<Node>
     */
    private function nodesInScope(array $nodes): array
    {
        $result = [];

        foreach ($nodes as $node) {
            $result[] = $node;

            if (! $node instanceof Node\FunctionLike && ! $node instanceof Node\Stmt\ClassLike) {
                \array_push($result, ...$this->nodesInScope($this->childNodes($node)));
            }
        }

        return $result;
    }

    /**
     * @param array<string, int> $assignments
     *
     * @return list<string>
     */
    private function referenceArguments(Node $node, Scope $scope, array $assignments): array
    {
        if (! $node instanceof Node\Expr\CallLike || $node->isFirstClassCallable()) {
            return [];
        }

        $function = $this->calledFunction($node, $scope, $assignments);
        $parameters = $function?->getVariants()[0]->getParameters();
        $names = [];

        foreach ($node->getArgs() as $position => $argument) {
            if ($argument->unpack || null === $parameters || $this->parameterForArgument($parameters, $argument, $position)?->passedByReference()->yes()) {
                \array_push($names, ...$this->assignmentNames($argument->value));
            }
        }

        return $names;
    }

    /**
     * @param list<ExtendedParameterReflection> $parameters
     */
    private function parameterForArgument(array $parameters, Node\Arg $argument, int $position): ExtendedParameterReflection|null
    {
        if (null === $argument->name && isset($parameters[$position])) {
            return $parameters[$position];
        }

        foreach ($parameters as $parameter) {
            if ($argument->name?->toString() === $parameter->getName()) {
                return $parameter;
            }
        }

        $last = \array_last($parameters);

        return null !== $last && $last->isVariadic() ? $last : null;
    }

    /**
     * @param array<string, int> $assignments
     */
    private function calledFunction(Node\Expr\CallLike $node, Scope $scope, array $assignments): ExtendedMethodReflection|FunctionReflection|null
    {
        if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
            return $this->reflectionProvider->hasFunction($node->name, $scope)
                ? $this->reflectionProvider->getFunction($node->name, $scope)
                : null;
        }

        if (($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\NullsafeMethodCall) && $node->name instanceof Node\Identifier) {
            return $this->stableReceiverMethod($node->var, $node->name->toString(), $scope, $assignments);
        }

        if ($node instanceof Node\Expr\StaticCall && $node->class instanceof Node\Name && $node->name instanceof Node\Identifier) {
            return $scope->getMethodReflection($scope->resolveTypeByName($node->class), $node->name->toString());
        }

        if ($node instanceof Node\Expr\New_ && $node->class instanceof Node\Name) {
            return $scope->getMethodReflection($scope->resolveTypeByName($node->class), '__construct');
        }

        return null;
    }

    /**
     * @param array<string, int> $assignments
     */
    private function stableReceiverMethod(Node\Expr $receiver, string $name, Scope $scope, array $assignments): ExtendedMethodReflection|null
    {
        if (! $receiver instanceof Node\Expr\Variable || ! \is_string($receiver->name) || isset($assignments[$receiver->name])) {
            return null;
        }

        $type = $scope->getType($receiver);

        return $type->getObjectClassNames() === [] ? null : $scope->getMethodReflection($type, $name);
    }

    /**
     * @return list<string>
     */
    private function writtenVariables(Node $node): array
    {
        $variables = match (true) {
            $node instanceof Node\Expr\AssignRef => [$node->var, $node->expr],
            $node instanceof Node\Expr\Closure => \array_map(
                static fn (Node\ClosureUse $use): Node\Expr\Variable => $use->var,
                \array_filter($node->uses, static fn (Node\ClosureUse $use): bool => $use->byRef),
            ),
            $node instanceof Node\Expr\Assign,
            $node instanceof Node\Expr\AssignOp, $node instanceof Node\Expr\PreInc,
            $node instanceof Node\Expr\PostInc, $node instanceof Node\Expr\PreDec,
            $node instanceof Node\Expr\PostDec => [$node->var],
            $node instanceof Node\Stmt\Unset_ => $node->vars,
            $node instanceof Node\Stmt\Catch_ => null === $node->var ? [] : [$node->var],
            $node instanceof Node\Stmt\Foreach_ => null === $node->keyVar ? [$node->valueVar] : [$node->valueVar, $node->keyVar],
            default => [],
        };
        $names = [];

        foreach ($variables as $variable) {
            \array_push($names, ...$this->assignmentNames($variable));
        }

        return $names;
    }

    /**
     * @return list<string>
     */
    private function assignmentNames(Node\Expr $target): array
    {
        if ($target instanceof Node\Expr\Variable && \is_string($target->name)) {
            return [$target->name];
        }

        if (! $target instanceof Node\Expr\List_ && ! $target instanceof Node\Expr\Array_) {
            return [];
        }

        $names = [];

        foreach ($target->items as $item) {
            if (null !== $item) {
                \array_push($names, ...$this->assignmentNames($item->value));
            }
        }

        return $names;
    }

    /**
     * @param array<string, int> $aliases
     * @param array<string, int> $writes
     *
     * @return array<string, int>
     */
    private function addStableAlias(Node $node, array $aliases, array $writes): array
    {
        if (! $node instanceof Node\Expr\Assign || ! $node->var instanceof Node\Expr\Variable || ! $node->expr instanceof Node\Expr\Variable) {
            return $aliases;
        }

        $target = $node->var->name;
        $source = $node->expr->name;

        if (\is_string($target) && \is_string($source) && 1 === ($writes[$target] ?? 0) && isset($aliases[$source])) {
            $aliases[$target] = $node->getEndFilePos();
        }

        return $aliases;
    }

    /**
     * @param array<string, int> $aliases
     */
    private function preservesCause(Node\Expr\New_ $exception, Scope $scope, array $aliases): bool|null
    {
        $parameter = $this->causeParameter($exception, $scope);

        if (null === $parameter) {
            return null;
        }

        [$index, $name] = $parameter;
        $argument = null;

        foreach ($exception->getArgs() as $position => $candidate) {
            if ($candidate->unpack) {
                return null;
            }

            if ($candidate->name?->toString() === $name || (null === $candidate->name && $position === $index)) {
                $argument = $candidate->value;
            }
        }

        if (null === $argument || $argument instanceof Node\Expr\ConstFetch) {
            return false;
        }

        if ($argument instanceof Node\Expr\Variable && \is_string($argument->name)) {
            return isset($aliases[$argument->name]) && $exception->getStartFilePos() > $aliases[$argument->name] ? true : null;
        }

        if ($argument instanceof Node\Expr\New_) {
            return $this->preservesCause($argument, $scope, $aliases);
        }

        return null;
    }

    /**
     * @return array{int, string}|null
     */
    private function causeParameter(Node\Expr\New_ $exception, Scope $scope): array|null
    {
        if (! $exception->class instanceof Node\Name) {
            return null;
        }

        $name = $scope->resolveName($exception->class);

        if (! $this->reflectionProvider->hasClass($name)) {
            return null;
        }

        $class = $this->reflectionProvider->getClass($name);

        if (! $class->implementsInterface(Throwable::class) || ! $class->hasConstructor()) {
            return null;
        }

        $parameters = $class->getConstructor()->getVariants()[0]->getParameters();
        $candidates = [];

        foreach ($parameters as $index => $parameter) {
            if (! $this->acceptsCause($parameter)) {
                continue;
            }

            if ($parameter->getName() === 'previous') {
                return [$index, $parameter->getName()];
            }

            $candidates[] = [$index, $parameter->getName()];
        }

        return \count($candidates) === 1 ? $candidates[0] : null;
    }

    private function acceptsCause(ExtendedParameterReflection $parameter): bool
    {
        $type = TypeCombinator::removeNull($parameter->getType());

        return $type->getObjectClassNames() !== [] && new ObjectType(Throwable::class)->isSuperTypeOf($type)->yes();
    }

    /**
     * @return list<Node>
     */
    private function childNodes(Node $node): array
    {
        $children = [];

        foreach ($node->getSubNodeNames() as $name) {
            $value = $node->{$name};

            if ($value instanceof Node) {
                $children[] = $value;
            } elseif (\is_array($value)) {
                foreach ($value as $child) {
                    if ($child instanceof Node) {
                        $children[] = $child;
                    }
                }
            }
        }

        return $children;
    }
}
