<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Node\ClassPropertyNode;
use Noldors\PHPStan\Eloquent\Arguments;
use Noldors\PHPStan\Eloquent\ModelScope;
use PHPStan\Reflection\ReflectionProvider;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

/**
 * @phpstan-import-type Registration from EloquentRegistrationCollector
 *
 * @implements Collector<ClassPropertyNode, Registration>
 */
final class EloquentProviderObserverCollector implements Collector
{
    public function __construct(
        private readonly ModelScope $models,
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        if (! $this->models->isEnabled() || 'observers' !== $node->getName() || ! $node->getClassReflection()->is(EventServiceProvider::class) || null === $node->getDefault()) {
            return null;
        }
        $classes = [];

        foreach ($scope->getType($node->getDefault())->getConstantArrays() as $array) {
            foreach ($array->getKeyTypes() as $index => $key) {
                $model = Arguments::string($key);

                if (null !== $model && $this->reflectionProvider->hasClass($model) && $this->models->contains($this->reflectionProvider->getClass($model))) {
                    $classes = [...$classes, ...Arguments::strings($array->getValueTypes()[$index])];
                }
            }
        }

        return ['kind' => 'observers', 'classes' => $classes, 'models' => [], 'aliases' => [], 'line' => $node->getStartLine()];
    }
}
