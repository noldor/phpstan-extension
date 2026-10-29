<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use Closure;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use Noldors\PHPStan\Eloquent\Arguments;

final class BuiltinBuilderRules
{
    private const array SCALARS = [
        'accepted' => 'accepted', 'activeurl' => 'active_url', 'base64' => 'base64', 'bail' => 'bail',
        'boolean' => 'boolean', 'declined' => 'declined', 'exclude' => 'exclude', 'filled' => 'filled',
        'hexcolor' => 'hex_color', 'json' => 'json', 'list' => 'list', 'macaddress' => 'mac_address',
        'missing' => 'missing', 'nullable' => 'nullable', 'present' => 'present', 'prohibited' => 'prohibited',
        'required' => 'required', 'sometimes' => 'sometimes', 'ulid' => 'ulid',
    ];

    public function __construct(
        private readonly RuleNames $names,
        private readonly FluentBuilderRules $fluent,
        private readonly FileBuilderRules $files,
        private readonly NestedBuilderRules $nested,
    ) {}

    /**
     * @param Closure(Node\Expr): RuleSet $read
     */
    public function read(Node\Expr $expression, Scope $scope, Closure $read): RuleSet|null
    {
        $scalar = $this->scalarFactory($expression, $scope);

        if (null !== $scalar) {
            return new RuleSet([$scalar]);
        }
        $classes = $scope->getType($expression)->getObjectClassNames();

        if (1 !== \count($classes) || ! \str_starts_with($classes[0], 'Illuminate\\Validation\\Rules\\')) {
            return null;
        }
        $class = \mb_substr($classes[0], \mb_strlen('Illuminate\\Validation\\Rules\\'));
        $calls = [];
        $root = $expression;

        while ($root instanceof Node\Expr\MethodCall && $root->name instanceof Node\Identifier && ! $root->isFirstClassCallable()) {
            \array_unshift($calls, ['method' => \mb_strtolower($root->name->toString()), 'arguments' => \array_values($root->getArgs())]);
            $root = $root->var;
        }
        $factory = $this->factory($root, $scope);

        if (null === $factory || ! $root instanceof Node\Expr\CallLike) {
            return new RuleSet($this->names->allRules($scope->getType($expression)), unknown: true);
        }

        return match ($class) {
            'StringRule', 'Numeric', 'Date' => $this->fluent->read($class, $factory, $calls, $scope),
            'File', 'ImageFile' => $this->files->read($class, $factory, \array_values($root->getArgs()), $calls, $scope, $read),
            'Email', 'Password' => $this->nested->read($class, $factory, $calls, $scope, $read),
            'IpAddress' => $this->ip($calls, $scope),
            'RequiredIf', 'RequiredUnless', 'ProhibitedIf', 'ProhibitedUnless' => $this->conditional($class, \array_values($root->getArgs()), $scope),
            default => new RuleSet($this->names->allRules($scope->getType($expression))),
        };
    }

    private function factory(Node\Expr $root, Scope $scope): string|null
    {
        if ($root instanceof Node\Expr\New_ && $root->class instanceof Node\Name && ! $root->isFirstClassCallable()) {
            return '__construct';
        }

        if (! $root instanceof Node\Expr\StaticCall || ! $root->class instanceof Node\Name || ! $root->name instanceof Node\Identifier || $root->isFirstClassCallable()) {
            return null;
        }
        $classes = $scope->resolveTypeByName($root->class)->getObjectClassNames();

        return 1 === \count($classes) && ('Illuminate\\Validation\\Rule' === $classes[0] || \str_starts_with($classes[0], 'Illuminate\\Validation\\Rules\\'))
            ? \mb_strtolower($root->name->toString()) : null;
    }

    /**
     * @param list<array{method: string, arguments: list<Node\Arg>}> $calls
     */
    private function ip(array $calls, Scope $scope): RuleSet
    {
        $rule = 'ip';

        foreach ($calls as $call) {
            if ('version' !== $call['method']) {
                continue;
            }
            $values = Arguments::type($call['arguments'], $scope, 'version', 0)?->getConstantScalarValues() ?? [];
            $rule = 1 === \count($values) && \in_array($values[0], [4, 6], true) ? 'ipv' . $values[0] : '';
        }

        return new RuleSet('' === $rule ? [] : [$rule], unknown: '' === $rule);
    }

    /**
     * @param list<Node\Arg> $arguments
     */
    private function conditional(string $class, array $arguments, Scope $scope): RuleSet
    {
        $type = Arguments::type($arguments, $scope, 'condition', 0) ?? Arguments::type($arguments, $scope, 'callback', 0);
        $values = $type?->getConstantScalarValues() ?? [];
        $rule = \str_starts_with($class, 'Required') ? 'required' : 'prohibited';
        $unless = \str_ends_with($class, 'Unless');

        if (1 === \count($values) && \is_bool($values[0])) {
            return new RuleSet($values[0] !== $unless ? [$rule] : []);
        }

        return new RuleSet([$rule]);
    }

    private function scalarFactory(Node\Expr $expression, Scope $scope): string|null
    {
        if (! $expression instanceof Node\Expr\StaticCall || ! $expression->class instanceof Node\Name || ! $expression->name instanceof Node\Identifier || $expression->isFirstClassCallable()) {
            return null;
        }
        $classes = $scope->resolveTypeByName($expression->class)->getObjectClassNames();

        return ['Illuminate\\Validation\\Rule'] === $classes ? self::SCALARS[\mb_strtolower($expression->name->toString())] ?? null : null;
    }
}
