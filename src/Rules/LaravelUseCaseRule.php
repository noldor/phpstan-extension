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
final class LaravelUseCaseRule implements Rule
{
    public function __construct(
        private readonly ClassScope $useCases,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $declaration = $node->getOriginalNode();
        $class = $node->getClassReflection();

        if (! $declaration instanceof Node\Stmt\Class_ || $class->isAbstract() || ! $this->useCases->contains($class)) {
            return [];
        }
        $errors = [];

        if (! $declaration->isFinal()) {
            $errors[] = RuleErrorBuilder::message('Declare concrete use cases final; configure an exclusion for an explicit extension contract.')->identifier('architecture.useCaseFinal')->build();
        }

        if (! $class->hasNativeMethod('handle') || ! $class->getNativeMethod('handle')->isPublic()) {
            $errors[] = RuleErrorBuilder::message('Expose the public handle() entry point on a use case.')->identifier('architecture.useCaseEntryPoint')->build();
        }

        if (null !== $declaration->name && \str_ends_with(\mb_strtolower($declaration->name->toString()), 'usecase')) {
            $errors[] = RuleErrorBuilder::message('Name a use case after its operation without a UseCase suffix.')->identifier('architecture.useCaseName')->build();
        }

        return $errors;
    }
}
