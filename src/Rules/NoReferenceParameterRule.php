<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedParameterReflection;

/**
 * @implements Rule<Node\FunctionLike>
 */
final class NoReferenceParameterRule implements Rule
{
    /**
     * @param list<string> $ownedPaths
     */
    public function __construct(
        private readonly FileHelper $fileHelper,
        private readonly array $ownedPaths,
    ) {}

    public function getNodeType(): string
    {
        return Node\FunctionLike::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $node instanceof Node\Stmt\Function_ && ! $node instanceof Node\Stmt\ClassMethod) {
            return [];
        }

        if (! $this->isOwned($scope->getFile())) {
            return [];
        }

        $errors = [];

        foreach ($node->getParams() as $index => $parameter) {
            if (! $parameter->byRef || ! $parameter->var instanceof Node\Expr\Variable || ! \is_string($parameter->var->name)) {
                continue;
            }

            if ($node instanceof Node\Stmt\ClassMethod && $this->requiresExternalReference($scope, $node, $index)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(\sprintf(
                'Return results instead of passing $%s by reference in an owned API.',
                $parameter->var->name,
            ))->identifier('api.referenceParameter')->line($parameter->getStartLine())->build();
        }

        return $errors;
    }

    private function requiresExternalReference(Scope $scope, Node\Stmt\ClassMethod $method, int $index): bool
    {
        if (3 === $index && $method->isPublic() && ! $method->isStatic() && 'stream_open' === \mb_strtolower($method->name->toString())) {
            return true;
        }
        $class = $scope->getClassReflection();

        if (null === $class) {
            return false;
        }

        foreach ([...$class->getParents(), ...\array_values($class->getInterfaces())] as $ancestor) {
            if ($this->externalParameterIsReference($ancestor, $method->name->toString(), $index)) {
                return true;
            }
        }

        return false;
    }

    private function externalParameterIsReference(ClassReflection $ancestor, string $methodName, int $index): bool
    {
        if (! $ancestor->hasNativeMethod($methodName)) {
            return false;
        }

        $method = $ancestor->getNativeMethod($methodName);
        $file = $method->getDeclaringClass()->getFileName();

        if ($method->isPrivate() || (null !== $file && $this->isOwned($file))) {
            return false;
        }

        if (\mb_strtolower($methodName) === '__construct' && ! $method->isAbstract()) {
            return false;
        }

        foreach ($method->getVariants() as $variant) {
            $parameter = $this->parameterAtPosition($variant->getParameters(), $index);

            if (null !== $parameter && $parameter->passedByReference()->yes()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<ExtendedParameterReflection> $parameters
     */
    private function parameterAtPosition(array $parameters, int $index): ExtendedParameterReflection|null
    {
        if (isset($parameters[$index])) {
            return $parameters[$index];
        }

        $last = \array_last($parameters);

        return null !== $last && $last->isVariadic() ? $last : null;
    }

    private function isOwned(string $file): bool
    {
        $file = $this->fileHelper->normalizePath($file, '/');

        foreach ($this->ownedPaths as $path) {
            $path = \mb_rtrim($this->fileHelper->normalizePath($this->fileHelper->absolutizePath($path), '/'), '/');

            if ($file === $path || \str_starts_with($file, $path . '/')) {
                return true;
            }
        }

        return false;
    }
}
