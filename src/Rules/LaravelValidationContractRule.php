<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Validation\Paths;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Validation\RuleNames;
use Noldors\PHPStan\Validation\FieldOrder;
use Nuwave\Lighthouse\Validation\Validator;
use Noldors\PHPStan\Validation\MethodArrays;
use Noldors\PHPStan\Configuration\ClassScope;
use Noldors\PHPStan\Validation\Configuration;
use Noldors\PHPStan\Validation\MessageCoverage;

/**
 * @implements Rule<InClassNode>
 */
final class LaravelValidationContractRule implements Rule
{
    public function __construct(
        private readonly ClassScope $validators,
        private readonly MethodArrays $arrays,
        private readonly FieldOrder $order,
        private readonly MessageCoverage $coverage,
        private readonly Paths $paths,
        private readonly RuleNames $names,
        private readonly Configuration $configuration,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $declaration = $node->getOriginalNode();
        $class = $node->getClassReflection();

        if (! $declaration instanceof Node\Stmt\Class_ || $class->isAbstract() || ! $this->validators->contains($class)) {
            return [];
        }
        $definitions = [];

        foreach (['rules', 'messages', 'attributes'] as $method) {
            $definitions[$method] = $this->arrays->read($class, $method, $scope);
        }
        $messages = $this->paths->flatten($definitions['messages']);
        $errors = $this->methodOrder($declaration);
        $errors = [...$errors, ...$this->fieldOrder($class, $definitions['rules'], $messages, $definitions['attributes'])];

        foreach ($this->coverage->missing($class, $scope, $messages) as $key) {
            $errors[] = $this->error('Provide an explicit public validation message for ' . $key . '.', 'messageCoverage');
        }

        return [...$errors, ...$this->lighthouseKeys($class, $definitions)];
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function methodOrder(Node\Stmt\Class_ $class): array
    {
        $previous = -1;

        foreach ($class->getMethods() as $method) {
            $position = \array_search(\mb_strtolower($method->name->toString()), ['rules', 'messages', 'attributes'], true);

            if (false === $position) {
                continue;
            }

            if ($position < $previous) {
                return [$this->error('Declare validation methods in rules(), messages(), attributes() order.', 'methodOrder')];
            }
            $previous = $position;
        }

        return [];
    }

    /**
     * @param array<string, Type>|null $rules
     * @param array<string, Type>|null $messages
     * @param array<string, Type>|null $attributes
     *
     * @return list<IdentifierRuleError>
     */
    private function fieldOrder(ClassReflection $class, array|null $rules, array|null $messages, array|null $attributes): array
    {
        if (null === $rules) {
            return [];
        }
        $fields = \array_keys($rules);
        $errors = [];

        if (! $this->order->rulesAreOrdered($fields, $this->configuration->relationships($class))) {
            $errors[] = $this->error('Place ordinary validation fields before contiguous relationship groups, with parent paths before their children.', 'fieldOrder');
        }

        if (null !== $messages && ! $this->order->followRules($fields, \array_keys($messages), true)) {
            $errors[] = $this->error('Group validation messages by field in rules() order.', 'messageOrder');
        }

        if (null !== $attributes && ! $this->order->followRules($fields, \array_keys($attributes), false)) {
            $errors[] = $this->error('Declare validation attributes in rules() field order.', 'attributeOrder');
        }

        return $errors;
    }

    /**
     * @param array<string, array<string, Type>|null> $definitions
     *
     * @return list<IdentifierRuleError>
     */
    private function lighthouseKeys(ClassReflection $class, array $definitions): array
    {
        return [
            ...$this->genericMessages($class, $definitions['messages']),
            ...$this->relativePaths($class, $definitions),
        ];
    }

    /**
     * @param array<string, Type>|null $messages
     *
     * @return list<IdentifierRuleError>
     */
    private function genericMessages(ClassReflection $class, array|null $messages): array
    {
        if (! $class->is(Validator::class)) {
            return [];
        }
        $errors = [];

        foreach (\array_keys($messages ?? []) as $key) {
            if ($this->names->isBuiltin($key)) {
                $errors[] = $this->error('Use a field-specific Lighthouse message key instead of the generic rule key ' . $key . '.', 'lighthouseMessageKey');
            }
        }

        return $errors;
    }

    /**
     * @param array<string, array<string, Type>|null> $definitions
     *
     * @return list<IdentifierRuleError>
     */
    private function relativePaths(ClassReflection $class, array $definitions): array
    {
        $fields = $this->configuration->inputFields($class);

        if ([] === $fields || \in_array('input', $fields, true)) {
            return [];
        }
        $errors = [];
        $definitions['messages'] = $this->paths->flatten($definitions['messages']);

        foreach ($definitions as $method => $entries) {
            foreach (\array_keys($entries ?? []) as $path) {
                $parts = \explode('.', $path);

                if ('input' === $parts[0] && \in_array($parts[1] ?? '', $fields, true)) {
                    $errors[] = $this->error('Use input-relative paths in ' . $method . '(); remove the extra input. prefix from ' . $path . '.', 'relativeInputPath');

                    break;
                }
            }
        }

        return $errors;
    }

    private function error(string $message, string $identifier): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier('validation.' . $identifier)->build();
    }
}
