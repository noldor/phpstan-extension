<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Type\UnionType;
use PHPStan\Type\ObjectType;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Node\MethodReturnStatementsNode;
use Noldors\PHPStan\Configuration\ClassScope;

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
final class MutableCollectionExposureRule implements Rule
{
    /**
     * @param list<string> $mutableCollectionTypes
     */
    public function __construct(
        private readonly ClassScope $owners,
        private readonly array $mutableCollectionTypes,
    ) {}

    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if (! $this->owners->contains($class) || $node->getMethodReflection()->isPrivate()) {
            return [];
        }

        $aliasReturns = $this->straightLineAliasReturns($node->getStatements(), $class);
        $errors = [];

        foreach ($node->getReturnStatements() as $returnStatement) {
            $return = $returnStatement->getReturnNode();

            if (null === $return->expr) {
                continue;
            }

            if (! $this->isInternalProperty($return->expr, $class) && ! isset($aliasReturns[\spl_object_id($return)])) {
                continue;
            }

            if (! $this->isMutableCollection($returnStatement->getScope()->getType($return->expr))) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message('Do not expose an internal mutable collection from an invariant owner; return a snapshot or a read-only view instead.')
                ->identifier('encapsulation.mutableCollectionExposure')
                ->line($return->getStartLine())
                ->build();
        }

        return $errors;
    }

    private function isInternalProperty(Node\Expr $expression, ClassReflection $class): bool
    {
        if (! $expression instanceof Node\Expr\PropertyFetch
            || ! $expression->var instanceof Node\Expr\Variable
            || 'this' !== $expression->var->name
            || ! $expression->name instanceof Node\Identifier
        ) {
            return false;
        }

        $name = $expression->name->toString();

        return $class->hasNativeProperty($name) && ! $class->getNativeProperty($name)->hasHook('get');
    }

    private function isMutableCollection(Type $type): bool
    {
        foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $member) {
            foreach ($this->mutableCollectionTypes as $class) {
                if (new ObjectType($class)->isSuperTypeOf($member)->yes()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param array<Node\Stmt> $statements
     *
     * @return array<int, true>
     */
    private function straightLineAliasReturns(array $statements, ClassReflection $class): array
    {
        $aliases = [];
        $returns = [];

        foreach ($statements as $statement) {
            if ($statement instanceof Node\Stmt\Return_
                && $statement->expr instanceof Node\Expr\Variable
                && \is_string($statement->expr->name)
                && isset($aliases[$statement->expr->name])
            ) {
                $returns[\spl_object_id($statement)] = true;
            }

            $aliases = $this->aliasesAfterStatement($statement, $aliases, $class);
        }

        return $returns;
    }

    /**
     * @param array<string, true> $aliases
     *
     * @return array<string, true>
     */
    private function aliasesAfterStatement(Node\Stmt $statement, array $aliases, ClassReflection $class): array
    {
        $assignment = $statement instanceof Node\Stmt\Expression ? $statement->expr : null;

        if (! $assignment instanceof Node\Expr\Assign
            || ! $assignment->var instanceof Node\Expr\Variable
            || ! \is_string($assignment->var->name)
        ) {
            return [];
        }

        $name = $assignment->var->name;

        if ($this->isInternalProperty($assignment->expr, $class)) {
            $aliases[$name] = true;
        } elseif ($assignment->expr instanceof Node\Expr\Variable && \is_string($assignment->expr->name)) {
            if (isset($aliases[$assignment->expr->name])) {
                $aliases[$name] = true;
            } else {
                unset($aliases[$name]);
            }
        } else {
            // Other expressions may rebind aliases through a call or reference.
            return [];
        }

        return $aliases;
    }
}
