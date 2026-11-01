<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\IdentifierRuleError;
use Noldors\PHPStan\Laravel\RetainedBindingFacts;
use Noldors\PHPStan\Laravel\RetainedBindingAliases;
use Noldors\PHPStan\Laravel\RetainedResolverPolicy;
use Noldors\PHPStan\Laravel\RetainedDependencyGraph;
use Noldors\PHPStan\Collectors\RetainedBindingCollector;
use Noldors\PHPStan\Collectors\RetainedResolverCollector;
use Noldors\PHPStan\Collectors\RetainedProviderBindingCollector;

/**
 * @phpstan-import-type BindingFact from RetainedBindingFacts
 *
 * @implements Rule<CollectedDataNode>
 */
final class RetainedResolverDependencyRule implements Rule
{
    public function __construct(
        private readonly RetainedResolverPolicy $policy,
        private readonly RetainedDependencyGraph $graph,
        private readonly RetainedBindingAliases $aliases,
    ) {}

    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if (! $this->policy->roots->isConfigured()) {
            return [];
        }
        $records = $this->bindingRecords($node);
        $bindings = [];
        $errors = $this->unknownBindingErrors($records);

        foreach ($records as $file => $registrations) {
            foreach ($registrations as $registration) {
                foreach ($registration['abstracts'] as $abstract) {
                    $bindings[RetainedResolverPolicy::key($abstract)][] = $registration;
                }
            }
        }
        $bindings = $this->aliases->normalize($bindings);

        foreach ($node->get(RetainedResolverCollector::class) as $file => $roots) {
            foreach ($roots as $root) {
                $problem = $this->policy->hasBindingSources()
                    ? $this->graph->check($root['name'], $bindings)
                    : ['identifier' => 'lighthouse.retainedUnknownDependency', 'message' => 'Configure production bindingSourcePaths to inspect retained resolver construction.'];

                if (null !== $problem) {
                    $errors[] = RuleErrorBuilder::message($problem['message'])->identifier($problem['identifier'])->file($file)->line($root['line'])->build();
                }
            }
        }

        return $errors;
    }

    /**
     * @return array<string, list<BindingFact>>
     */
    private function bindingRecords(CollectedDataNode $node): array
    {
        $records = [];
        $sources = [$node->get(RetainedBindingCollector::class), $node->get(RetainedProviderBindingCollector::class)];

        foreach ($sources as $source) {
            foreach ($source as $file => $declarations) {
                foreach ($declarations as $declaration) {
                    $records[$file] = [...($records[$file] ?? []), ...$declaration];
                }
            }
        }

        return $records;
    }

    /**
     * @param array<string, list<BindingFact>> $records
     *
     * @return list<IdentifierRuleError>
     */
    private function unknownBindingErrors(array $records): array
    {
        $errors = [];

        foreach ($records as $file => $registrations) {
            foreach ($registrations as $registration) {
                if ([] === $registration['abstracts']) {
                    $errors[] = RuleErrorBuilder::message('Cannot establish retained resolver construction: ' . $registration['reason'] . ' has no finite abstract target. Use explicit registrations in production binding sources.')->identifier('lighthouse.retainedUnknownBinding')->file($file)->line($registration['line'])->build();
                }
            }
        }

        return $errors;
    }
}
