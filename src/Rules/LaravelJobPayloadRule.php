<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Laravel\QueuePolicy;
use Noldors\PHPStan\Laravel\JobPayloadTypes;
use Noldors\PHPStan\Laravel\PropertyLocation;

/**
 * @implements Rule<InClassNode>
 */
final class LaravelJobPayloadRule implements Rule
{
    public function __construct(
        private readonly QueuePolicy $policy,
        private readonly JobPayloadTypes $types,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $declaration = $node->getOriginalNode();

        if (! $declaration instanceof Node\Stmt\Class_ || $class->isAbstract() || ! $this->policy->isJob($class)) {
            return [];
        }
        $errors = [];

        for ($owner = $class; null !== $owner; $owner = $owner->getParentClass()) {
            foreach ($owner->getNativeReflection()->getProperties() as $property) {
                if ($property->isStatic() || $property->isVirtual() || $property->getDeclaringClass()->getName() !== $owner->getName()) {
                    continue;
                }

                if (! $this->types->containsModel($owner->getNativeProperty($property->getName())->getReadableType())) {
                    continue;
                }
                $line = $owner->getName() === $class->getName() ? PropertyLocation::inClass($declaration, $property->getName()) : $declaration->getStartLine();
                $errors[] = RuleErrorBuilder::message(\sprintf('Store scalar identifiers or an explicit snapshot in a dedicated job instead of model payload %s::$%s.', $owner->getDisplayName(), $property->getName()))->identifier('laravel.jobModelPayload')->line($line)->build();
            }
        }

        return $errors;
    }
}
