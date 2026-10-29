<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Configuration\ClassScope;

/**
 * @implements Rule<InClassNode>
 */
final class LighthouseClassContractRule implements Rule
{
    public function __construct(
        private readonly ClassScope $validators,
        private readonly ClassScope $resolvers,
        private readonly ClassScope $subscriptions,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $declaration = $node->getOriginalNode();
        $class = $node->getClassReflection();

        if (! $declaration instanceof Node\Stmt\Class_) {
            return [];
        }
        $errors = [];

        if ($this->validators->contains($class)) {
            if (! $class->is('Nuwave\\Lighthouse\\Validation\\Validator')) {
                $errors[] = RuleErrorBuilder::message('Operation validators must extend the Lighthouse Validator.')->identifier('lighthouse.validatorBase')->build();
            }

            if (! $class->isAbstract() && ! $declaration->isFinal()) {
                $errors[] = RuleErrorBuilder::message('Declare concrete Lighthouse operation validators final.')->identifier('lighthouse.validatorFinal')->build();
            }
        }

        if (! $class->isAbstract() && $this->resolvers->contains($class) && (! $class->hasNativeMethod('__invoke') || ! $class->getNativeMethod('__invoke')->isPublic())) {
            $errors[] = RuleErrorBuilder::message('Expose a public __invoke() method on custom Lighthouse field handlers.')->identifier('lighthouse.resolverInvokable')->build();
        }

        if (! $class->isAbstract() && $this->subscriptions->contains($class) && ! $declaration->isFinal()) {
            $errors[] = RuleErrorBuilder::message('Declare concrete Lighthouse subscriptions final.')->identifier('lighthouse.subscriptionFinal')->build();
        }

        return $errors;
    }
}
