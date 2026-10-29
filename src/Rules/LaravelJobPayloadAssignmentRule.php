<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Laravel\QueuePolicy;
use Noldors\PHPStan\Laravel\JobPayloadTypes;

/**
 * @implements Rule<Node\Expr\Assign>
 */
final class LaravelJobPayloadAssignmentRule implements Rule
{
    public function __construct(
        private readonly QueuePolicy $policy,
        private readonly JobPayloadTypes $types,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr\Assign::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node->var instanceof Node\Expr\PropertyFetch || ! $node->var->name instanceof Node\Identifier || ! $this->types->containsModel($scope->getType($node->expr))) {
            return [];
        }
        $class = $this->policy->classOf($scope->getType($node->var->var));
        $name = $node->var->name->toString();

        if (null === $class || ! $this->policy->isJob($class) || ! $class->hasNativeProperty($name)) {
            return [];
        }
        $property = $class->getNativeProperty($name);

        if ($property->hasHook('set') || $this->types->containsModel($property->getReadableType())) {
            return [];
        }

        return [RuleErrorBuilder::message('Do not retain an Eloquent model or model collection in a dedicated job; assign an identifier or an explicit snapshot.')->identifier('laravel.jobModelPayload')->build()];
    }
}
