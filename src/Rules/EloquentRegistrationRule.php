<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use Noldors\PHPStan\Collectors\EloquentClassCollector;
use Noldors\PHPStan\Collectors\EloquentMorphUsageCollector;
use Noldors\PHPStan\Collectors\EloquentRegistrationCollector;
use Noldors\PHPStan\Collectors\EloquentDateSerializerCollector;
use Noldors\PHPStan\Collectors\EloquentProviderObserverCollector;

/**
 * @phpstan-import-type ClassRecord from EloquentClassCollector
 *
 * @implements Rule<CollectedDataNode>
 */
final class EloquentRegistrationRule implements Rule
{
    /**
     * @param list<string> $trustedDateSerializers
     */
    public function __construct(
        private readonly array $trustedDateSerializers,
    ) {}

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $registration = $this->registrations($node);
        $observers = $registration['observers'];
        $aliases = $registration['aliases'];
        $mappedModels = $registration['models'];
        $errors = $registration['errors'];
        $classes = $node->get(EloquentClassCollector::class);

        foreach ($node->get(EloquentProviderObserverCollector::class) as $registrations) {
            foreach ($registrations as $registration) {
                $observers = [...$observers, ...$registration['classes']];
            }
        }

        foreach ($classes as $records) {
            foreach ($records as $record) {
                $observers = [...$observers, ...$record['observers']];
            }
        }
        $serializers = $this->verifiedSerializers($node);

        foreach ($classes as $file => $records) {
            foreach ($records as $record) {
                $errors = [...$errors, ...$this->classErrors($record, $file, $observers, $mappedModels, $serializers)];
            }
        }

        return [...$errors, ...$this->usageErrors($node, $aliases)];
    }

    /**
     * @return array{observers: list<string>, models: list<string>, aliases: array<string, string>, errors: list<IdentifierRuleError>}
     */
    private function registrations(CollectedDataNode $node): array
    {
        $observers = [];
        $models = [];
        $aliases = [];
        $errors = [];

        foreach ($node->get(EloquentRegistrationCollector::class) as $file => $registrations) {
            foreach ($registrations as $registration) {
                $observers = [...$observers, ...$registration['classes']];
                $models = [...$models, ...$registration['models']];
                $aliases = \array_merge($aliases, $registration['aliases']);

                if ('unknownMap' === $registration['kind']) {
                    $errors[] = $this->error('Cannot verify a dynamic morph map; expose a statically resolvable alias-to-model registration.', 'laravel.morphMapUnverifiable', $file, $registration['line']);
                }
            }
        }

        return ['observers' => $observers, 'models' => $models, 'aliases' => $aliases, 'errors' => $errors];
    }

    /** @param array<string, string> $aliases
     * @return list<IdentifierRuleError>
     */
    private function usageErrors(CollectedDataNode $node, array $aliases): array
    {
        $errors = [];

        foreach ($node->get(EloquentMorphUsageCollector::class) as $file => $usages) {
            foreach ($usages as $usage) {
                if (isset($aliases[$usage['alias']])) {
                    $errors[] = $this->error('Resolve application morph aliases through Relation::getMorphAlias(Model::class).', 'laravel.morphAlias', $file, $usage['line']);
                }
            }
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function verifiedSerializers(CollectedDataNode $node): array
    {
        $methods = \array_map(\mb_strtolower(...), $this->trustedDateSerializers);

        foreach ($node->get(EloquentDateSerializerCollector::class) as $records) {
            foreach ($records as $record) {
                if ($record['valid']) {
                    $methods[] = $record['method'];
                }
            }
        }

        return $methods;
    }

    /**
     * @param ClassRecord $record
     * @param list<string> $observers
     * @param list<string> $mappedModels
     * @param list<string> $serializers
     *
     * @return list<IdentifierRuleError>
     */
    private function classErrors(array $record, string $file, array $observers, array $mappedModels, array $serializers): array
    {
        $errors = [];

        if (! $record['final'] && ($record['observer'] || \in_array($record['name'], $observers, true))) {
            $errors[] = $this->error('Declare application model observer classes final.', 'laravel.observerFinal', $file, $record['line']);
        }

        if (! $record['model'] || $record['abstract']) {
            return $errors;
        }

        if (! \in_array($record['name'], $mappedModels, true)) {
            $errors[] = $this->error('Register every concrete application model in Relation::morphMap() or Relation::enforceMorphMap().', 'laravel.morphMap', $file, $record['line']);
        }

        $verifiedSerializer = \in_array($record['serializer'], $serializers, true);
        $verifiedCasts = '' === $record['castsMethod'] || \in_array($record['castsMethod'], $serializers, true);

        if (! $verifiedSerializer || ! $verifiedCasts) {
            $errors[] = $this->error('Use serializeDate() that formats JSON dates with the configured microseconds and explicit timezone offset; the default serializer is insufficient.', 'laravel.modelJsonDateFormat', $file, $record['line']);
        }

        return $errors;
    }

    private function error(string $message, string $identifier, string $file, int $line): IdentifierRuleError
    {
        return RuleErrorBuilder::message($message)->identifier($identifier)->file($file)->line($line)->build();
    }
}
