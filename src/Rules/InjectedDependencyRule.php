<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PhpParser\NodeFinder;
use PHPStan\Analyser\Scope;
use PHPStan\Php\PhpVersion;
use PHPStan\Type\TypeCombinator;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Node\ClassPropertiesNode;
use PHPStan\Node\Property\PropertyWrite;
use Noldors\PHPStan\Configuration\ClassScope;

/**
 * @implements Rule<ClassPropertiesNode>
 */
final class InjectedDependencyRule implements Rule
{
    public function __construct(
        private readonly ClassScope $owners,
        private readonly PhpVersion $phpVersion,
    ) {}

    public function getNodeType(): string
    {
        return ClassPropertiesNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClass();

        if (! $class instanceof Node\Stmt\Class_ || ! $this->owners->contains($node->getClassReflection())) {
            return [];
        }
        $constructor = $class->getMethod('__construct');

        if (null === $constructor) {
            return [];
        }
        $initializations = $this->initializations($constructor);
        $errors = [];

        foreach ($node->getProperties() as $property) {
            $name = $property->getName();

            if (! $this->isDependency($property) || ! isset($initializations[$name])) {
                continue;
            }
            $initialization = $initializations[$name];

            if ($this->shouldPromote($property, $initialization)) {
                $errors[] = RuleErrorBuilder::message(\sprintf('Promote retained dependency $%s into the constructor.', $name))->identifier('api.dependencyPromotion')->line($property->getStartLine())->build();
            }

            if ($this->shouldBeReadonly($property, $node, $class)) {
                $errors[] = RuleErrorBuilder::message(\sprintf('Declare the construction-only dependency reference $%s readonly.', $name))->identifier('api.dependencyReadonly')->line($property->getStartLine())->build();
            }
        }

        return $errors;
    }

    /**
     * @param array{parameter: Node\Param, early: bool} $initialization
     */
    private function shouldPromote(ClassPropertyNode $property, array $initialization): bool
    {
        $parameter = $initialization['parameter'];

        return $this->phpVersion->getVersionId() >= 80_000 && ! $property->isPromoted() && $initialization['early']
            && $parameter->var instanceof Node\Expr\Variable && $property->getName() === $parameter->var->name
            && $this->canPromote($property, $parameter);
    }

    private function shouldBeReadonly(ClassPropertyNode $property, ClassPropertiesNode $node, Node\Stmt\Class_ $class): bool
    {
        $name = $property->getName();

        return $this->phpVersion->supportsReadOnlyProperties() && ! $property->isReadOnly()
            && ! $this->hasHydrationHooks($class) && ! $this->hasReferenceEscape($class, $name)
            && $this->hasSingleConstructorWrite($node, $name);
    }

    private function isDependency(ClassPropertyNode $property): bool
    {
        $type = $property->getNativeType();

        return $property->isPrivate() && ! $property->isStatic() && ! $property->isDeclaredInTrait() && ! $property->hasHooks()
            && null !== $type && TypeCombinator::removeNull($type)->isObject()->yes();
    }

    /**
     * @return array<string, array{parameter: Node\Param, early: bool}>
     */
    private function initializations(Node\Stmt\ClassMethod $constructor): array
    {
        $parameters = [];
        $result = [];

        foreach ($constructor->params as $parameter) {
            if (! $parameter->var instanceof Node\Expr\Variable || ! \is_string($parameter->var->name) || $parameter->byRef) {
                continue;
            }
            $parameters[$parameter->var->name] = $parameter;

            if ($parameter->isPromoted()) {
                $result[$parameter->var->name] = ['parameter' => $parameter, 'early' => true];
            }
        }
        $early = true;

        foreach ($constructor->stmts ?? [] as $statement) {
            $assignment = $this->retainedAssignment($statement, $parameters);

            if (null === $assignment) {
                $early = false;

                continue;
            }
            [$name, $parameter] = $assignment;
            $result[$name] = ['parameter' => $parameter, 'early' => $early];
        }

        return $result;
    }

    /**
     * @param array<string, Node\Param> $parameters
     *
     * @return array{string, Node\Param}|null
     */
    private function retainedAssignment(Node\Stmt $statement, array $parameters): array|null
    {
        if (! $statement instanceof Node\Stmt\Expression || ! $statement->expr instanceof Node\Expr\Assign) {
            return null;
        }
        $assignment = $statement->expr;
        $property = $assignment->var;
        $value = $assignment->expr;

        if (! $property instanceof Node\Expr\PropertyFetch || ! $property->name instanceof Node\Identifier || ! $property->var instanceof Node\Expr\Variable || 'this' !== $property->var->name) {
            return null;
        }

        if (! $value instanceof Node\Expr\Variable || ! \is_string($value->name) || ! isset($parameters[$value->name])) {
            return null;
        }

        return [$property->name->toString(), $parameters[$value->name]];
    }

    private function canPromote(ClassPropertyNode $property, Node\Param $parameter): bool
    {
        return $property->getDefault() === null && $property->getAttrGroups() === [] && [] === $parameter->attrGroups
            && $this->typeKey($property->getNativeTypeNode()) === $this->typeKey($parameter->type) && ! $parameter->variadic;
    }

    private function typeKey(Node\ComplexType|Node\Identifier|Node\Name|null $type): string
    {
        if ($type instanceof Node\Name || $type instanceof Node\Identifier) {
            return \mb_strtolower($type->toString());
        }

        if ($type instanceof Node\NullableType) {
            return '?' . $this->typeKey($type->type);
        }

        if ($type instanceof Node\UnionType || $type instanceof Node\IntersectionType) {
            return \implode($type instanceof Node\UnionType ? '|' : '&', \array_map(fn ($part): string => $this->typeKey($part), $type->types));
        }

        return '';
    }

    private function hasHydrationHooks(Node\Stmt\Class_ $class): bool
    {
        foreach (['__clone', '__unserialize', '__wakeup', '__set', '__get'] as $method) {
            if ($class->getMethod($method) !== null) {
                return true;
            }
        }

        return false;
    }

    private function hasSingleConstructorWrite(ClassPropertiesNode $node, string $name): bool
    {
        $count = 0;

        foreach ($node->getPropertyUsages() as $usage) {
            if (! $usage instanceof PropertyWrite || $usage->isViaOffsetAccess()) {
                continue;
            }
            $fetch = $usage->getFetch();

            if (! $fetch->name instanceof Node\Identifier) {
                return false;
            }

            if ($fetch->name->toString() !== $name) {
                continue;
            }

            if ($usage->getScope()->getFunctionName() !== '__construct') {
                return false;
            }
            ++$count;
        }

        return 1 === $count;
    }

    private function hasReferenceEscape(Node\Stmt\Class_ $class, string $name): bool
    {
        return new NodeFinder()->findFirst($class->stmts, fn (Node $node): bool => $this->invalidatesReadonly($node, $name)) !== null;
    }

    private function invalidatesReadonly(Node $node, string $name): bool
    {
        if ($node instanceof Node\Stmt\Unset_) {
            return $this->containsProperty($node->vars, $name);
        }

        if ($node instanceof Node\Expr\AssignRef) {
            return $this->containsProperty([$node->var, $node->expr], $name);
        }

        if (($node instanceof Node\Stmt\ClassMethod || $node instanceof Node\Expr\Closure) && $node->byRef) {
            return $this->containsProperty($node->stmts ?? [], $name);
        }

        if ($node instanceof Node\Expr\ArrowFunction && $node->byRef) {
            return $this->containsProperty([$node->expr], $name);
        }

        return false;
    }

    /**
     * @param array<Node> $nodes
     */
    private function containsProperty(array $nodes, string $name): bool
    {
        return new NodeFinder()->findFirst($nodes, static fn (Node $node): bool => $node instanceof Node\Expr\PropertyFetch
            && $node->var instanceof Node\Expr\Variable && 'this' === $node->var->name
            && (! $node->name instanceof Node\Identifier || $node->name->toString() === $name)) !== null;
    }
}
