<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Type\VerbosityLevel;
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

        if (! $this->models->contains($class) || $class->isAbstract()) {
            return [];
        }
        $names = $this->metadata->databaseNames($class);

        if (null === $names) {
            return [];
        }

        if ([] === $names) {
            return [RuleErrorBuilder::message('Cannot verify model PHPDoc without independent schema metadata; configure Larastan migrations or declare dynamic table/connection configuration.')->identifier('laravel.modelSchema')->build()];
        }
        $entries = null === $comment ? [] : $this->documents->entries($comment->getText(), $comment->getStartLine());
        $errors = $this->columnErrors($class, $names, \array_column($entries, 'name'), $node->getStartLine());
        $group = 0;

        foreach ($entries as $entry) {
            $name = $entry['name'];
            $tag = $this->documents->property($class, $name);

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
     * @param list<string> $names
     * @param list<string> $localNames
     *
     * @return list<IdentifierRuleError>
     */
    private function columnErrors(ClassReflection $class, array $names, array $localNames, int $line): array
    {
        $errors = [];

        foreach ($names as $name) {
            $tag = $this->documents->property($class, $name);

            if (null === $tag || null === $tag['readable']) {
                $errors[] = $this->missingColumn($name, $line);

                continue;
            }

            if (! \in_array($name, $localNames, true)) {
                $errors = [...$errors, ...$this->propertyErrors($class, $name, $tag, true, true, $line)];
            }
        }

        return $errors;
    }

    private function missingColumn(string $name, int $line): IdentifierRuleError
    {
        return $this->error(\sprintf('Document database column $%s with a readable @property or @property-read annotation, locally or through inheritance.', $name), 'laravel.modelPropertyMissing', $line);
    }

    /**
     * @param array{readable: Type|null, writable: bool} $tag
     *
     * @return list<IdentifierRuleError>
     */
    private function propertyErrors(ClassReflection $class, string $name, array $tag, bool $database, bool $known, int $line): array
    {
        $expected = $this->metadata->readableType($class, $name, $database);

        if (! $known) {
            return [$this->error(\sprintf('Unknown model property $%s; use a real column/accessor/relation or register an intentional query-derived property.', $name), 'laravel.modelPropertyName', $line)];
        }
        $errors = [];
        $readonly = $this->metadata->keyName($class) === $name || (! $database && ($this->metadata->isRelation($class, $name) || $this->metadata->isReadonlyAccessor($class, $name)));

        if ($readonly && $tag['writable'] && ! $this->writableProperties->contains($class, $name)) {
            $errors[] = $this->error(\sprintf('Document read-only model property $%s with @property-read.', $name), 'laravel.modelPropertyReadOnly', $line);
        }
        $documented = $tag['readable'];
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
