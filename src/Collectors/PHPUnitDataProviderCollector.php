<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Collectors\Collector;
use Noldors\PHPStan\Eloquent\Arguments;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\AttributeReflection;

/**
 * @implements Collector<InClassNode, list<string>>
 */
final class PHPUnitDataProviderCollector implements Collector
{
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param Scope&DependencyTracker $scope
     */
    public function processNode(Node $node, Scope $scope): array|null
    {
        $class = $node->getClassReflection();

        if ($class->isTrait() || $class->isInterface() || ! $class->is('PHPUnit\\Framework\\TestCase')) {
            return null;
        }
        $providers = [];

        foreach ($this->references($class) as $reference) {
            $scope->trackClassDependency($reference['class']);

            if (! $this->reflectionProvider->hasClass($reference['class'])) {
                continue;
            }
            $provider = $this->provider($this->reflectionProvider->getClass($reference['class']), $reference['method']);

            if (null !== $provider) {
                $providers[] = $provider;
            }
        }

        return \array_values(\array_unique($providers));
    }

    /**
     * @return list<array{class: string, method: string}>
     */
    private function references(ClassReflection $class): array
    {
        $references = [];

        foreach ($class->getNativeReflection()->getMethods() as $method) {
            if (! $method->isPublic()) {
                continue;
            }

            foreach ($class->getNativeMethod($method->getName())->getAttributes() as $attribute) {
                $reference = $this->reference($attribute, $class);

                if (null !== $reference) {
                    $references[] = $reference;
                }
            }
        }

        return $references;
    }

    /**
     * @return array{class: string, method: string}|null
     */
    private function reference(AttributeReflection $attribute, ClassReflection $testClass): array|null
    {
        $arguments = $attribute->getArgumentTypes();
        $class = match ($attribute->getName()) {
            'PHPUnit\\Framework\\Attributes\\DataProvider' => $testClass->getName(),
            'PHPUnit\\Framework\\Attributes\\DataProviderExternal' => Arguments::string($arguments['className'] ?? null),
            default => null,
        };
        $method = Arguments::string($arguments['methodName'] ?? null);

        return null !== $class && null !== $method ? ['class' => $class, 'method' => $method] : null;
    }

    private function provider(ClassReflection $class, string $method): string|null
    {
        if (! $class->hasNativeMethod($method)) {
            return null;
        }
        $provider = $class->getNativeMethod($method);

        if (! $provider->isPublic() || ! $provider->isStatic()) {
            return null;
        }

        return \mb_strtolower($provider->getDeclaringClass()->getName() . '::' . $provider->getName());
    }
}
