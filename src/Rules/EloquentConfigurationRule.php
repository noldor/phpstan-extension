<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Eloquent\ModelScope;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\Eloquent\DateCastCheck;
use Noldors\PHPStan\Eloquent\ModelConstructor;
use Noldors\PHPStan\Eloquent\PropertyExceptions;

/**
 * @implements Rule<ClassPropertyNode>
 */
final class EloquentConfigurationRule implements Rule
{
    private const array ATTRIBUTES = [
        'table' => 'Table', 'primaryKey' => 'Table', 'keyType' => 'Table',
        'incrementing' => 'WithoutIncrementing', 'timestamps' => 'WithoutTimestamps',
        'connection' => 'Connection', 'dateFormat' => 'DateFormat',
        'fillable' => 'Fillable', 'guarded' => 'Guarded', 'hidden' => 'Hidden',
        'visible' => 'Visible', 'appends' => 'Appends', 'touches' => 'Touches',
        'refreshes' => 'Refreshes', 'builder' => 'UseEloquentBuilder',
        'collectionClass' => 'CollectedBy', 'factory' => 'UseFactory',
    ];

    public function __construct(
        private readonly ModelScope $models,
        private readonly PropertyExceptions $dynamicConfiguration,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly DateCastCheck $casts,
        private readonly ModelConstructor $constructors,
    ) {}

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if (! $this->models->contains($class) || null === $node->getDefault() || $this->dynamicConfiguration->contains($class, $node->getName()) || $this->constructors->mutates($class, $node->getName())) {
            return [];
        }
        $name = $node->getName();

        if ('casts' === $name) {
            return [RuleErrorBuilder::message('Define static model casts through casts(); preserve inherited and dynamic casts.')->identifier('laravel.modelCasts')->build(), ...$this->casts->errors($scope->getType($node->getDefault()), $class)];
        }
        $attribute = self::ATTRIBUTES[$name] ?? null;

        if (null === $attribute || ! $this->reflectionProvider->hasClass('Illuminate\\Database\\Eloquent\\Attributes\\' . $attribute)) {
            return [];
        }

        return [RuleErrorBuilder::message(\sprintf('Configure static model $%s with Laravel attributes (%s); declare dynamic/package exceptions explicitly.', $name, $attribute))
            ->identifier('laravel.modelConfiguration')->build()];
    }
}
