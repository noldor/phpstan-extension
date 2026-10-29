<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Collectors;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Collectors\Collector;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\RestrictedUsage\ForbiddenApis;
use Noldors\PHPStan\PHPUnit\PHPUnitProviderExecutionVisitor;

/**
 * @phpstan-type ProviderEffect array{provider: string, file: string, line: int, position: int, api: string}
 *
 * @implements Collector<Node\Expr, ProviderEffect>
 */
final class PHPUnitProviderEffectCollector implements Collector
{
    private readonly ForbiddenApis $apis;

    /**
     * @param list<array{class: string, methods: list<string>}> $forbiddenMethods
     * @param list<string> $forbiddenFunctions
     */
    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
        array $forbiddenMethods,
        array $forbiddenFunctions,
    ) {
        $this->apis = new ForbiddenApis($forbiddenMethods, $forbiddenFunctions);
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array|null
    {
        $method = $node->getAttribute(PHPUnitProviderExecutionVisitor::METHOD_ATTRIBUTE);
        $class = $scope->getClassReflection();

        if (! \is_string($method) || null === $class || ! $class->hasNativeMethod($method) || ! $node instanceof Node\Expr\CallLike || $node->isFirstClassCallable()) {
            return null;
        }
        $api = $this->forbiddenCall($node, $scope);

        if (null === $api) {
            return null;
        }
        $provider = $class->getNativeMethod($method);

        return [
            'provider' => \mb_strtolower($provider->getDeclaringClass()->getName() . '::' . $provider->getName()),
            'file' => $scope->getTraitReflection()?->getFileName() ?? $scope->getFile(),
            'line' => $node->getStartLine(), 'position' => $node->getStartFilePos(), 'api' => $api,
        ];
    }

    private function forbiddenCall(Node\Expr\CallLike $node, Scope $scope): string|null
    {
        if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name) {
            if (! $this->reflectionProvider->hasFunction($node->name, $scope)) {
                return null;
            }
            $function = $this->reflectionProvider->getFunction($node->name, $scope)->getName();

            return $this->apis->matchesFunction($function) ? $function . '()' : null;
        }

        if (($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\NullsafeMethodCall) && $node->name instanceof Node\Identifier) {
            return $this->forbiddenMethod($scope->getType($node->var), $node->name->toString(), $scope);
        }

        if ($node instanceof Node\Expr\StaticCall && $node->name instanceof Node\Identifier) {
            $type = $node->class instanceof Node\Name ? $scope->resolveTypeByName($node->class) : $scope->getType($node->class)->getClassStringObjectType();

            return $this->forbiddenMethod($type, $node->name->toString(), $scope);
        }

        if ($node instanceof Node\Expr\New_ && ! $node->class instanceof Node\Stmt\Class_) {
            $type = $node->class instanceof Node\Name ? $scope->resolveTypeByName($node->class) : $scope->getType($node->class)->getClassStringObjectType();

            return $this->forbiddenMethod($type, '__construct', $scope);
        }

        return null;
    }

    private function forbiddenMethod(Type $type, string $name, Scope $scope): string|null
    {
        if (! $type->hasMethod($name)->yes()) {
            return null;
        }
        $method = $scope->getMethodReflection($type, $name);

        return null !== $method && $this->apis->matchesMethod($method->getDeclaringClass(), $method->getName())
            ? $method->getDeclaringClass()->getName() . '::' . $method->getName() . '()' : null;
    }
}
