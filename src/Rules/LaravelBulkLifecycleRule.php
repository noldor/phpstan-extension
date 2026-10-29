<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Configuration\ClassScope;
use Noldors\PHPStan\Configuration\ClassTraits;

/**
 * @implements Rule<Node\Expr>
 */
final class LaravelBulkLifecycleRule implements Rule
{
    /**
     * @param list<string> $softDeleteTraits
     */
    public function __construct(
        private readonly ClassScope $models,
        private readonly ClassScope $bulkOperations,
        private readonly array $softDeleteTraits,
    ) {}

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof Node\Expr\MethodCall || ! $node->name instanceof Node\Identifier || $this->bulkOperations->contains($scope->getClassReflection())) {
            return [];
        }

        if (! \in_array(\mb_strtolower($node->name->toString()), ['delete', 'restore', 'forcedelete'], true) || ! $this->targetsSoftDeleteModel($scope->getType($node->var))) {
            return [];
        }

        return [RuleErrorBuilder::message('Use individual model delete(), restore() or forceDelete(); configure a documented bulk-operation exception when required.')->identifier('laravel.bulkLifecycle')->build()];
    }

    private function targetsSoftDeleteModel(Type $receiver): bool
    {
        $modelType = $this->modelType($receiver);

        foreach ($modelType?->getObjectClassReflections() ?? [] as $class) {
            if (! $this->models->contains($class) || ! $class->is('Illuminate\\Database\\Eloquent\\Model')) {
                continue;
            }

            foreach ($this->softDeleteTraits as $trait) {
                if (ClassTraits::contains($class, $trait)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function modelType(Type $receiver): Type|null
    {
        if (new ObjectType('Illuminate\\Database\\Eloquent\\Builder')->isSuperTypeOf($receiver)->yes()) {
            return $receiver->getTemplateType('Illuminate\\Database\\Eloquent\\Builder', 'TModel');
        }

        if (new ObjectType('Illuminate\\Database\\Eloquent\\Relations\\Relation')->isSuperTypeOf($receiver)->yes()) {
            return $receiver->getTemplateType('Illuminate\\Database\\Eloquent\\Relations\\Relation', 'TRelatedModel');
        }

        return null;
    }
}
