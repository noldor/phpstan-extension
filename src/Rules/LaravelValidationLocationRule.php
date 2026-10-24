<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use Illuminate\Http\Request;
use PHPStan\File\FileHelper;
use PHPStan\Type\ObjectType;
use PHPStan\Rules\RuleErrorBuilder;
use Illuminate\Validation\Validator;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ReflectionProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Validation\Rule as LaravelRule;
use Illuminate\Contracts\Validation\InvokableRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Request as RequestFacade;
use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Nuwave\Lighthouse\Validation\Validator as GraphqlValidator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

/**
 * @implements Rule<Node>
 */
final class LaravelValidationLocationRule implements Rule
{
    /**
     * @param list<string> $allowedValidationPaths
     */
    public function __construct(
        private readonly FileHelper $fileHelper,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly array $allowedValidationPaths,
    ) {}

    public function getNodeType(): string
    {
        return Node::class;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($scope->isInClass() && $this->isValidatorFile($scope->getFile())) {
            return [];
        }

        if (! $this->definesValidation($node, $scope)) {
            return [];
        }

        return [RuleErrorBuilder::message('Define Laravel validation only in validator classes under the configured allowed paths; invoke a prepared validator here instead.')
            ->identifier('architecture.validationLocation')
            ->build()];
    }

    private function isValidatorFile(string $file): bool
    {
        $file = $this->fileHelper->normalizePath($file, '/');

        foreach ($this->allowedValidationPaths as $directory) {
            $directory = $this->fileHelper->absolutizePath($directory);
            $directory = $this->fileHelper->normalizePath($directory, '/');

            if (\str_starts_with($file, \mb_rtrim($directory, '/') . '/')) {
                return true;
            }
        }

        return false;
    }

    private function definesValidation(Node $node, Scope $scope): bool
    {
        return match (true) {
            $node instanceof Node\Expr\FuncCall => $this->isValidatorHelper($node, $scope),
            $node instanceof Node\Expr\StaticCall => $this->isStaticDeclaration($node, $scope),
            // PHPStan also visits the MethodCall synthesized for a nullsafe call.
            $node instanceof Node\Expr\MethodCall => $this->isMethodDeclaration($node, $scope),
            $node instanceof Node\Expr\New_ => $this->isValidationConstruction($node, $scope),
            $node instanceof Node\Stmt\ClassMethod => $this->isRulesMethod($node, $scope),
            default => false,
        };
    }

    private function isValidatorHelper(Node\Expr\FuncCall $node, Scope $scope): bool
    {
        if (! $node->name instanceof Node\Name || ! $this->reflectionProvider->hasFunction($node->name, $scope)) {
            return false;
        }

        $name = $this->reflectionProvider->getFunction($node->name, $scope)->getName();

        // The zero-argument helper only retrieves the factory; it declares no rules.
        return 'validator' === \mb_strtolower($name) && ($node->isFirstClassCallable() || [] !== $node->getArgs());
    }

    private function isStaticDeclaration(Node\Expr\StaticCall $node, Scope $scope): bool
    {
        if (! $node->name instanceof Node\Identifier) {
            return false;
        }

        $type = $node->class instanceof Node\Name
            ? $scope->resolveTypeByName($node->class)
            : $scope->getType($node->class)->getClassStringObjectType();

        $name = \mb_strtolower($node->name->toString());

        return $this->isRuleType($type)
            || $this->isFactoryDeclaration($type, $name)
            || $this->isRequestDeclaration($type, $name)
            || $this->isControllerDeclaration($node, $scope, $type, $name);
    }

    private function isMethodDeclaration(Node\Expr\MethodCall $node, Scope $scope): bool
    {
        if (! $node->name instanceof Node\Identifier) {
            return false;
        }

        $name = \mb_strtolower($node->name->toString());
        $type = $scope->getType($node->var);

        if ($this->isFactoryDeclaration($type, $name) || $this->isRuleBuilderDeclaration($type, $name, $scope)) {
            return true;
        }

        if ($this->hasType($type, ValidatorContract::class) && \in_array($name, ['setrules', 'addrules', 'sometimes', 'after'], true)) {
            return true;
        }

        if ($this->isRequestDeclaration($type, $name)) {
            return true;
        }

        return $this->isControllerDeclaration($node, $scope, $type, $name);
    }

    private function isFactoryDeclaration(Type $type, string $name): bool
    {
        return \in_array($name, ['make', 'validate', 'extend', 'extendimplicit', 'extenddependent'], true)
            && ($this->hasType($type, Factory::class) || $this->hasType($type, ValidatorFacade::class));
    }

    private function isRuleBuilderDeclaration(Type $type, string $name, Scope $scope): bool
    {
        if (! $this->isRuleType($type)) {
            return false;
        }

        $method = $scope->getMethodReflection($type, $name);

        if (null === $method) {
            return false;
        }

        foreach ($method->getVariants() as $variant) {
            if ($this->isRuleType($variant->getReturnType())) {
                return true;
            }
        }

        return false;
    }

    private function isRequestDeclaration(Type $type, string $name): bool
    {
        return \in_array($name, ['validate', 'validatewithbag'], true)
            && ($this->hasType($type, Request::class) || $this->hasType($type, RequestFacade::class));
    }

    private function isControllerDeclaration(Node\Expr\MethodCall|Node\Expr\StaticCall $node, Scope $scope, Type $type, string $name): bool
    {
        if (! \in_array($name, ['validate', 'validatewithbag', 'validatewith'], true)) {
            return false;
        }

        foreach ($type->getObjectClassReflections() as $class) {
            if (! $class->hasTraitUse(ValidatesRequests::class)) {
                continue;
            }

            if ('validatewith' !== $name || $node->isFirstClassCallable()) {
                return true;
            }

            foreach ($node->getArgs() as $position => $argument) {
                if ('validator' === $argument->name?->toString() || (0 === $position && null === $argument->name)) {
                    return ! new ObjectType(ValidatorContract::class)->isSuperTypeOf($scope->getType($argument->value))->yes();
                }
            }

            return true;
        }

        return false;
    }

    private function isValidationConstruction(Node\Expr\New_ $node, Scope $scope): bool
    {
        $type = match (true) {
            $node->class instanceof Node\Name => $scope->resolveTypeByName($node->class),
            $node->class instanceof Node\Expr => $scope->getType($node->class)->getClassStringObjectType(),
            default => $scope->getType($node),
        };

        if ($this->isRuleType($type)) {
            return true;
        }

        foreach ($type->getObjectClassReflections() as $class) {
            if ($class->is(Validator::class)) {
                $file = $class->getFileName();

                return null === $file || ! $this->isValidatorFile($file);
            }
        }

        return false;
    }

    private function isRulesMethod(Node\Stmt\ClassMethod $node, Scope $scope): bool
    {
        if ('rules' !== \mb_strtolower($node->name->toString())) {
            return false;
        }

        $class = $scope->getClassReflection();

        return null !== $class && ($class->is(FormRequest::class) || $class->is(GraphqlValidator::class));
    }

    private function isRuleType(Type $type): bool
    {
        foreach ([LaravelRule::class, ValidationRule::class, RuleContract::class, InvokableRule::class] as $class) {
            if ($this->hasType($type, $class)) {
                return true;
            }
        }

        foreach ($type->getObjectClassNames() as $class) {
            if (\str_starts_with($class, 'Illuminate\\Validation\\Rules\\')) {
                return true;
            }
        }

        return false;
    }

    private function hasType(Type $type, string $class): bool
    {
        foreach ($type->getObjectClassNames() as $objectClass) {
            if (new ObjectType($class)->isSuperTypeOf(new ObjectType($objectClass))->yes()) {
                return true;
            }
        }

        return false;
    }
}
