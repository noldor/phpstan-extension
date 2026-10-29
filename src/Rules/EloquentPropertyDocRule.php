<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Type\VerbosityLevel;
use PHPStan\PhpDoc\Tag\PropertyTag;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Larastan\MigrationNullability;
use Noldors\PHPStan\Larastan\{ModelPropertyMetadata, PropertyTypeCheck};
use Noldors\PHPStan\Eloquent\{ModelScope, PropertyDoc, PropertyExceptions};

/**
 * @implements Rule<InClassNode>
 */
final class EloquentPropertyDocRule implements Rule
{
    public function __construct(
        private readonly ModelScope $models,
        private readonly ModelPropertyMetadata $metadata,
        private readonly PropertyDoc $documents,
        private readonly PropertyTypeCheck $types,
        private readonly MigrationNullability $nullability,
        private readonly PropertyExceptions $writableProperties,
        private readonly PropertyExceptions $queryProperties,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $comment = $node->getOriginalNode()->getDocComment();

        if (! $this->models->contains($class) || null === $comment || $class->isAbstract()) {
            return [];
        }
        $names = $this->metadata->databaseNames($class);

        if (null === $names) {
            return [];
        }

        if ([] === $names) {
            return [RuleErrorBuilder::message('Cannot verify model PHPDoc without independent schema metadata; configure Larastan migrations or declare dynamic table/connection configuration.')->identifier('laravel.modelSchema')->build()];
        }
        $errors = [];
        $group = 0;

        foreach ($this->documents->entries($comment->getText(), $comment->getStartLine()) as $entry) {
            $name = $entry['name'];
            $tag = $class->getPropertyTags()[$name] ?? null;

            if (null === $tag) {
                continue;
            }
            $database = \in_array($name, $names, true);
            $nextGroup = $database ? 1 : ($this->metadata->isRelation($class, $name) ? 3 : 2);
            $known = $database || $this->metadata->isRelation($class, $name) || $this->metadata->isAccessor($class, $name) || $this->queryProperties->contains($class, $name);

            if ($known && $nextGroup < $group) {
                $errors[] = $this->error('Order model PHPDoc as database fields, computed/query attributes, then relationships.', 'laravel.modelPropertyOrder', $entry['line']);
            }

            if ($known) {
                $group = \max($group, $nextGroup);
            }
            $errors = [...$errors, ...$this->propertyErrors($class, $name, $tag, $database, $known, $entry['line'])];
        }

        return $errors;
    }

    /**
     * @return list<IdentifierRuleError>
     */
    private function propertyErrors(ClassReflection $class, string $name, PropertyTag $tag, bool $database, bool $known, int $line): array
    {
        $expected = $this->metadata->readableType($class, $name, $database);

        if (! $known) {
            return [$this->error(\sprintf('Unknown model property $%s; use a real column/accessor/relation or register an intentional query-derived property.', $name), 'laravel.modelPropertyName', $line)];
        }
        $errors = [];
        $readonly = $this->metadata->keyName($class) === $name || (! $database && ($this->metadata->isRelation($class, $name) || $this->metadata->isReadonlyAccessor($class, $name)));

        if ($readonly && $tag->isWritable() && ! $this->writableProperties->contains($class, $name)) {
            $errors[] = $this->error(\sprintf('Document read-only model property $%s with @property-read.', $name), 'laravel.modelPropertyReadOnly', $line);
        }
        $documented = $tag->getReadableType();
        $reliableNullability = ! $database || $this->metadata->isReadableAccessor($class, $name) || $this->nullability->isReliable($name);

        if (null !== $expected && null !== $documented && ! $this->types->matches($expected, $documented, $reliableNullability)) {
            $errors[] = $this->error(\sprintf('PHPDoc for $%s must describe its readable value (%s), including supported generic/shape information.', $name, $expected->describe(VerbosityLevel::precise())), 'laravel.modelPropertyType', $line);
        }

        return $errors;
    }

    private function error(string $message, string $identifier, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier($identifier)->line($line)->build();
    }
}
