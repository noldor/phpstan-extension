<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use Noldors\PHPStan\Eloquent\Arguments;
use PHPStan\Reflection\ClassReflection;

final class RuleExpressions
{
    public function __construct(
        private readonly MethodArrays $arrays,
        private readonly RuleNames $names,
        private readonly BuiltinBuilderRules $builders,
        private readonly RuleListItems $lists,
    ) {}

    /**
     * @return array<string, RuleSet>|null
     */
    public function read(ClassReflection $class, Scope $scope): array|null
    {
        $expressions = $this->arrays->expressions($class, 'rules', $scope);

        return null === $expressions ? null : \array_map(fn (Node\Expr $expression): RuleSet => $this->expression($expression, $scope), $expressions);
    }

    private function expression(Node\Expr $expression, Scope $scope, bool $split = true): RuleSet
    {
        $items = $this->lists->read($expression, $scope);

        if (null !== $items) {
            $rules = new RuleSet();

            foreach ($items as $item) {
                $rules = $rules->append($this->expression($item, $scope, false));
            }

            return $rules;
        }
        $conditional = $this->conditional($expression, $scope);

        if (null !== $conditional) {
            return $conditional;
        }
        $builder = $this->builders->read($expression, $scope, fn (Node\Expr $nested): RuleSet => $this->expression($nested, $scope));

        if (null !== $builder) {
            return $builder;
        }
        $type = $scope->getType($expression);

        return new RuleSet($this->names->allRules($type, $split), unknown: ! $type->isObject()->yes() && [] === $type->getConstantStrings());
    }

    private function conditional(Node\Expr $expression, Scope $scope): RuleSet|null
    {
        if (! $expression instanceof Node\Expr\StaticCall || ! $expression->class instanceof Node\Name || ! $expression->name instanceof Node\Identifier || $expression->isFirstClassCallable()) {
            return null;
        }
        $classes = $scope->resolveTypeByName($expression->class)->getObjectClassNames();
        $method = \mb_strtolower($expression->name->toString());

        if (['Illuminate\\Validation\\Rule'] !== $classes || ! \in_array($method, ['when', 'unless'], true)) {
            return null;
        }
        $condition = Arguments::type($expression->getArgs(), $scope, 'condition', 0)?->getConstantScalarValues() ?? [];
        $if = Arguments::expression($expression->getArgs(), 'rules', 1);
        $else = Arguments::expression($expression->getArgs(), 'defaultRules', 2);

        if (1 !== \count($condition) || ! \is_bool($condition[0])) {
            return new RuleSet(unknown: true);
        }
        $selected = $condition[0] !== ('unless' === $method) ? $if : $else;

        return null === $selected ? new RuleSet() : $this->expression($selected, $scope);
    }
}
