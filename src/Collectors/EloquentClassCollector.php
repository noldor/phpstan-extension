<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Collectors\Collector;
use Illuminate\Database\Eloquent\Model;
use Noldors\PHPStan\Eloquent\Arguments;
use Noldors\PHPStan\Eloquent\ModelScope;
use Noldors\PHPStan\Eloquent\PropertyExceptions;

/**
 * @phpstan-type ClassRecord array{name: string, line: int, model: bool, observer: bool, final: bool, abstract: bool, observers: list<string>, serializer: string, castsMethod: string}
 *
 * @implements Collector<InClassNode, ClassRecord>
 */
final class EloquentClassCollector implements Collector
{
    public function __construct(
        private readonly ModelScope $models,
        private readonly PropertyExceptions $dynamicConfiguration,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        if (! $this->models->isEnabled()) {
            return null;
        }
        $class = $node->getClassReflection();

        if ($class->isTrait() || $class->isInterface() || $class->isAnonymous()) {
            return null;
        }
        $model = $this->models->contains($class);
        $observers = [];

        foreach ([$class, ...$class->getParents()] as $ancestor) {
            foreach ($ancestor->getAttributes() as $attribute) {
                if ('Illuminate\\Database\\Eloquent\\Attributes\\ObservedBy' === $attribute->getName()) {
                    $observers = [...$observers, ...Arguments::strings($attribute->getArgumentTypes()['classes'] ?? null)];
                }
            }
        }

        return [
            'name' => $class->getName(), 'line' => $node->getStartLine(),
            'model' => $model, 'observer' => $this->models->isObserver($class),
            'final' => $class->isFinalByKeyword(), 'abstract' => $class->isAbstract(),
            'observers' => $model ? $observers : [],
            'serializer' => $model ? \mb_strtolower($class->getNativeMethod('serializeDate')->getDeclaringClass()->getName() . '::serializeDate') : '',
            'castsMethod' => $model && ! $this->dynamicConfiguration->contains($class, 'casts') && $class->getNativeMethod('casts')->getDeclaringClass()->getName() !== Model::class
                ? \mb_strtolower($class->getNativeMethod('casts')->getDeclaringClass()->getName() . '::casts') : '',
        ];
    }
}
