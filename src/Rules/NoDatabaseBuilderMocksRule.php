<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\Configuration\ClassScope;

/**
 * @implements Rule<Node\Expr>
 */
final class NoDatabaseBuilderMocksRule implements Rule
{
    private const array MOCK_APIS = [
        \PHPUnit\Framework\TestCase::class => ['createMock', 'createStub', 'createPartialMock', 'getMockBuilder', 'createConfiguredMock', 'createConfiguredStub', 'getStubBuilder'],
        'mockery' => ['mock', 'spy'],
        \Illuminate\Foundation\Testing\Concerns\InteractsWithContainer::class => ['mock', 'partialMock', 'spy'],
    ];

    private readonly Type $forbiddenType;

    /**
     * @param list<string> $forbiddenTypes
     */
    public function __construct(
        private readonly ClassScope $tests,
        private readonly ReflectionProvider $reflectionProvider,
        array $forbiddenTypes,
    ) {
        $this->forbiddenType = TypeCombinator::union(...\array_map(
            static fn (string $class): ObjectType => new ObjectType(\mb_ltrim($class, '\\')),
            $forbiddenTypes,
        ));
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->tests->contains($scope->getClassReflection()) || ! ($node instanceof Node\Expr\MethodCall || $node instanceof Node\Expr\NullsafeMethodCall || $node instanceof Node\Expr\StaticCall) || $node->isFirstClassCallable()) {
            return [];
        }
        $parameter = $this->targetParameter($node, $scope);

        if (null === $parameter) {
            return [];
        }
        $target = $this->targetArgument($node, $parameter);

        if (null === $target || ! $this->isForbiddenTarget($scope->getType($target))) {
            return [];
        }

        return [RuleErrorBuilder::message('Exercise database builders and relationships against an isolated test database instead of mocking or stubbing them.')
            ->identifier('phpunit.databaseBuilderMock')
            ->build()];
    }

    private function targetParameter(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $call, Scope $scope): string|null
    {
        if (! $call->name instanceof Node\Identifier) {
            return null;
        }
        $receiver = $call instanceof Node\Expr\StaticCall
            ? ($call->class instanceof Node\Name ? $scope->resolveTypeByName($call->class) : $scope->getType($call->class)->getClassStringObjectType())
            : $scope->getType($call->var);
        $method = $scope->getMethodReflection($receiver, $call->name->toString());

        if (null === $method) {
            return null;
        }
        $class = $method->getDeclaringClass();

        if (! $class->hasNativeMethod($call->name->toString())) {
            return null;
        }

        foreach (self::MOCK_APIS as $owner => $names) {
            if (! $this->reflectionProvider->hasClass($owner)) {
                continue;
            }
            $api = $this->reflectionProvider->getClass($owner);

            foreach ($names as $name) {
                if ($this->hasNativeImplementation($class, $call->name->toString(), $api, $name)) {
                    $parameters = $api->getNativeMethod($name)->getVariants()[0]->getParameters();

                    return ($parameters[0] ?? null)?->getName();
                }
            }
        }

        return null;
    }

    private function hasNativeImplementation(ClassReflection $class, string $methodName, ClassReflection $api, string $apiMethod): bool
    {
        if (! $api->hasNativeMethod($apiMethod)) {
            return false;
        }
        $method = $class->getNativeReflection()->getMethod($methodName);
        $native = $api->getNativeReflection()->getMethod($apiMethod);

        return $method->getFileName() === $native->getFileName() && $method->getStartLine() === $native->getStartLine();
    }

    private function targetArgument(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $call, string $parameter): Node\Expr|null
    {
        foreach ($call->getArgs() as $argument) {
            if ($argument->name?->toString() === $parameter && ! $argument->unpack) {
                return $argument->value;
            }
        }
        $first = $call->getArgs()[0] ?? null;

        return null !== $first && null === $first->name && ! $first->unpack ? $first->value : null;
    }

    private function isForbiddenTarget(Type $type): bool
    {
        $names = $type->getConstantStrings();

        if ([] !== $names && TypeCombinator::union(...$names)->isSuperTypeOf($type)->yes()) {
            $classes = [];

            foreach ($names as $name) {
                if (! $this->reflectionProvider->hasClass($name->getValue())) {
                    return false;
                }
                $classes[] = new ObjectType($name->getValue());
            }

            return $this->forbiddenType->isSuperTypeOf(TypeCombinator::union(...$classes))->yes();
        }

        return $type->isClassString()->yes() && $this->forbiddenType->isSuperTypeOf($type->getClassStringObjectType())->yes();
    }
}
