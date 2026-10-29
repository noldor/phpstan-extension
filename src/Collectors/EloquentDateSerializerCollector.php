<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use DateTimeInterface;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Collectors\Collector;
use Noldors\PHPStan\Eloquent\Arguments;
use Noldors\PHPStan\Eloquent\ModelScope;
use Noldors\PHPStan\Eloquent\DateCastCheck;
use PHPStan\Node\MethodReturnStatementsNode;

/**
 * @implements Collector<MethodReturnStatementsNode, array{method: string, valid: bool}>
 */
final class EloquentDateSerializerCollector implements Collector
{
    public function __construct(
        private readonly ModelScope $models,
        private readonly string $jsonDateFormat,
        private readonly DateCastCheck $casts,
    ) {}

    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        if (! $this->models->isEnabled() || ! \in_array(\mb_strtolower($node->getMethodName()), ['serializedate', 'casts'], true)) {
            return null;
        }
        $valid = [] !== $node->getReturnStatements() && ! $node->getStatementResult()->hasYield() && $node->getStatementResult()->isAlwaysTerminating();

        foreach ($node->getReturnStatements() as $return) {
            $expression = $return->getReturnNode()->expr;
            $valid = $valid && ('casts' === $node->getMethodName()
                ? null !== $expression && $this->casts->isVerified($return->getScope()->getType($expression), $node->getClassReflection())
                : $this->isValidReturn($expression, $return->getScope()));
        }

        return [
            'method' => \mb_strtolower($node->getClassReflection()->getName() . '::' . $node->getMethodName()),
            'valid' => $valid,
        ];
    }

    private function isValidReturn(Node\Expr|null $expression, Scope $scope): bool
    {
        if (! $expression instanceof Node\Expr\MethodCall || ! $expression->name instanceof Node\Identifier || 'format' !== \mb_strtolower($expression->name->toString())) {
            return false;
        }

        if (! new ObjectType(DateTimeInterface::class)->isSuperTypeOf($scope->getType($expression->var))->yes()) {
            return false;
        }

        return Arguments::string(Arguments::type($expression->getArgs(), $scope, 'format', 0)) === $this->jsonDateFormat;
    }
}
