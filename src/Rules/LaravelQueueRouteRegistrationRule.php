<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Collectors\LaravelQueueRouteCollector;
use Noldors\PHPStan\Collectors\LaravelQueueProducerCollector;

/**
 * @implements Rule<CollectedDataNode>
 */
final class LaravelQueueRouteRegistrationRule implements Rule
{
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $classes = [];
        $queues = [];
        $records = $node->get(LaravelQueueRouteCollector::class);

        foreach ($node->get(LaravelQueueProducerCollector::class) as $declarations) {
            foreach ($declarations as $declaration) {
                if ('producer' === $declaration['kind']) {
                    $classes = [...$classes, ...$declaration['classes']];
                    $queues = [...$queues, ...$declaration['queues']];
                }
            }
        }
        $errors = [];

        foreach ($records as $file => $registrations) {
            foreach ($registrations as $registration) {
                if ('producer' !== $registration['kind'] && ([] !== \array_intersect(\array_map(strtolower(...), $registration['classes']), $classes) || [] !== \array_intersect($registration['queues'], $queues))) {
                    $errors[] = RuleErrorBuilder::message('Keep the selected producer queue route inside its class; do not register or forward it externally.')->identifier('laravel.externalQueueRoute')->file($file)->line($registration['line'])->build();
                }
            }
        }

        return $errors;
    }
}
