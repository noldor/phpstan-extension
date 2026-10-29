<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Laravel\QueueRouting;

/**
 * @phpstan-import-type QueueRoute from LaravelQueueRouteCollector
 *
 * @implements Collector<InClassNode, QueueRoute>
 */
final class LaravelQueueProducerCollector implements Collector
{
    public function __construct(
        private readonly QueueRouting $routing,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        return $this->declaration($node->getClassReflection(), $node->getStartLine());
    }

    /**
     * @return QueueRoute|null
     */
    private function declaration(ClassReflection $class, int $line): array|null
    {
        $role = $this->routing->role($class);

        if (null === $role || $class->isInterface() || $class->isTrait()) {
            return null;
        }
        $names = [];

        foreach ([$class, ...$class->getParents(), ...$class->getInterfaces(), ...$class->getTraits(true)] as $ancestor) {
            $names[] = \mb_strtolower($ancestor->getName());
        }
        $queues = $this->strings($this->routing->attribute($class, 'queue'));

        if ($class->hasNativeProperty('queue') && $class->getNativeProperty('queue')->isPublic()) {
            $default = $class->getNativeReflection()->getProperty('queue')->getBetterReflection()->getDefaultValueExpression();

            if (null !== $default) {
                $queues = [...$queues, ...$this->strings($this->routing->value($class->getNativeProperty('queue')->getDeclaringClass(), $default))];
            }
        }

        if ('listener' === $role && $this->routing->publicMethod($class, 'viaQueue')) {
            $queues = [...$queues, ...$this->strings($this->routing->methodValue($class, 'viaQueue'))];
        }

        if ('notification' === $role && $this->routing->publicMethod($class, 'viaQueues')) {
            foreach ($this->routing->methodValue($class, 'viaQueues')?->getConstantArrays() ?? [] as $array) {
                $queues = [...$queues, ...$this->strings($array->getIterableValueType())];
            }
        }

        return ['kind' => 'producer', 'classes' => $names, 'queues' => \array_values(\array_unique($queues)), 'line' => $line];
    }

    /**
     * @return list<string>
     */
    private function strings(Type|null $type): array
    {
        return \array_map(static fn ($string): string => $string->getValue(), $type?->getConstantStrings() ?? []);
    }
}
