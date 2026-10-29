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
use Noldors\PHPStan\Configuration\ClassScope;

/**
 * @implements Rule<Node\Expr>
 */
final class LaravelUseCaseExceptionRule implements Rule
{
    /**
     * @param list<string> $validationExceptions
     * @param list<string> $errorBagClasses
     */
    public function __construct(
        private readonly ClassScope $useCases,
        private readonly array $validationExceptions,
        private readonly array $errorBagClasses,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->useCases->contains($scope->getClassReflection())) {
            return [];
        }

        if (! $this->isValidationThrow($node, $scope) && ! ($node instanceof Node\Expr\New_ && $this->matchesType($scope->getType($node), $this->errorBagClasses))) {
            return [];
        }

        return [RuleErrorBuilder::message('Keep validation errors at the entry-point boundary; use a domain exception inside a use case.')->identifier('architecture.useCaseValidation')->build()];
    }

    private function isValidationThrow(Node\Expr $node, Scope $scope): bool
    {
        if (! $node instanceof Node\Expr\Throw_ || ! $this->matchesType($scope->getType($node->expr), $this->validationExceptions)) {
            return false;
        }
        $expression = $node->expr;

        return ! ($expression instanceof Node\Expr\StaticCall && $expression->name instanceof Node\Identifier && $expression->class instanceof Node\Name && $this->matchesType(new ObjectType($scope->resolveName($expression->class)), $this->validationExceptions) && \strcasecmp($expression->name->toString(), 'withMessages') === 0);
    }

    /**
     * @param list<string> $classes
     */
    private function matchesType(Type $type, array $classes): bool
    {
        if ($type instanceof UnionType) {
            foreach ($type->getTypes() as $member) {
                if ($this->matchesType($member, $classes)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($classes as $class) {
            if (new ObjectType($class)->isSuperTypeOf($type)->yes()) {
                return true;
            }
        }

        return false;
    }
}
