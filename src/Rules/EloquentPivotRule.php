<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Node\MethodReturnStatementsNode;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Noldors\PHPStan\Eloquent\{ModelScope, PivotInverseCheck, PivotMethod, PivotState};

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
final class EloquentPivotRule implements Rule
{
    public function __construct(
        private readonly ModelScope $models,
        private readonly PivotMethod $method,
        private readonly PivotInverseCheck $inverses,
    ) {}

    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->models->contains($node->getClassReflection())) {
            return [];
        }
        $errors = [];

        foreach ($node->getReturnStatements() as $return) {
            $expression = $return->getReturnNode()->expr;

            if (null === $expression || ! new ObjectType(BelongsToMany::class)->isSuperTypeOf($return->getScope()->getType($expression))->yes()) {
                continue;
            }
            $state = $this->method->returnedState($node->getStatements(), $return->getReturnNode(), $return->getScope());
            $error = $this->stateError($state, $node->getClassReflection()->getName());

            if (null !== $error) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function stateError(PivotState|null $state, string $owner): IdentifierRuleError|null
    {
        if (null === $state) {
            return RuleErrorBuilder::message('Cannot verify the returned many-to-many relation; expose its effective pivot and chaperone configuration.')->identifier('laravel.pivotUnverifiable')->build();
        }

        if (! $this->inverses->isCustomPivot($state)) {
            return RuleErrorBuilder::message('Select a custom Pivot (MorphPivot for morph relations) on each application many-to-many direction.')->identifier('laravel.pivotClass')->build();
        }

        if (null === $state->declaringInverse || null === $state->relatedInverse) {
            return RuleErrorBuilder::message('Configure chaperone(declaring: ..., related: ...) after selecting the pivot and preserve it on the returned relation.')->identifier('laravel.pivotChaperone')->build();
        }

        if (! $this->inverses->hasCorrectInverses($state, $owner)) {
            return RuleErrorBuilder::message('Pivot inverse names must resolve to belongsTo/morphTo relationships targeting the declaring and related models in this direction.')->identifier('laravel.pivotInverse')->build();
        }

        return null;
    }
}
