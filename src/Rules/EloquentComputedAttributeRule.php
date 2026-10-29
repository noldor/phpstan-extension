<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use Illuminate\Support\Str;
use PHPStan\Analyser\Scope;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Node\MethodReturnStatementsNode;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Noldors\PHPStan\Larastan\ModelPropertyMetadata;
use Noldors\PHPStan\Eloquent\{AccessorShape, ModelScope};

/**
 * @implements Rule<MethodReturnStatementsNode>
 */
final class EloquentComputedAttributeRule implements Rule
{
    public function __construct(
        private readonly ModelScope $models,
        private readonly ModelPropertyMetadata $metadata,
        private readonly AccessorShape $accessors,
    ) {}

    public function getNodeType(): string
    {
        return MethodReturnStatementsNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        $legacy = \preg_match('/^get(.+)Attribute$/', $node->getMethodName(), $match) === 1;

        if (! $this->models->contains($class)) {
            return [];
        }
        $names = $this->metadata->databaseNames($class);

        if (null === $names || [] === $names) {
            return [];
        }
        $name = Str::snake($node->getMethodName());

        if ($legacy) {
            $name = Str::snake($match[1]);
        }

        if (\in_array($name, $names, true) || ! $this->metadata->isReadonlyAccessor($class, $name)) {
            return [];
        }

        if ($legacy) {
            return [RuleErrorBuilder::message('Define computed read-only accessors with Attribute::make(get: ...) and Attribute<T, never>.')->identifier('laravel.computedAttribute')->build()];
        }
        $errors = $this->constructionErrors($node);
        $type = $node->getMethodReflection()->getVariants()[0]->getReturnType();

        if (! new ObjectType(Attribute::class)->isSuperTypeOf($type)->yes() || ! $type->getTemplateType(Attribute::class, 'TSet') instanceof NeverType) {
            $errors[] = RuleErrorBuilder::message('Document computed read-only attributes as Attribute<T, never>.')->identifier('laravel.computedAttribute')->build();
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function constructionErrors(MethodReturnStatementsNode $node): array
    {
        $factory = $this->accessors->factoryForMethod($node->getClassReflection(), $node->getMethodName());

        if (null === $factory || ! $factory->name instanceof Node\Identifier || 'make' !== \mb_strtolower($factory->name->toString())) {
            return [RuleErrorBuilder::message('Use Attribute::make(get: ...) for computed read-only attributes.')->identifier('laravel.computedAttribute')->build()];
        }

        return [];
    }
}
