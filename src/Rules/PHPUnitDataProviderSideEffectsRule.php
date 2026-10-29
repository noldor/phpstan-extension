<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Collectors\PHPUnitDataProviderCollector;
use Noldors\PHPStan\Collectors\PHPUnitProviderEffectCollector;

/**
 * @implements Rule<CollectedDataNode>
 */
final class PHPUnitDataProviderSideEffectsRule implements Rule
{
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $providers = [];

        foreach ($node->get(PHPUnitDataProviderCollector::class) as $records) {
            foreach ($records as $methods) {
                foreach ($methods as $method) {
                    $providers[$method] = true;
                }
            }
        }
        $errors = [];

        foreach ($node->get(PHPUnitProviderEffectCollector::class) as $records) {
            foreach ($records as $effect) {
                if (! isset($providers[$effect['provider']])) {
                    continue;
                }
                $key = $effect['file'] . ':' . $effect['position'];
                $errors[$key] = RuleErrorBuilder::message(\sprintf('Keep PHPUnit data providers free of database, I/O and mock creation; defer %s until test setup or execution.', $effect['api']))
                    ->identifier('phpunit.providerSideEffect')->file($effect['file'])->line($effect['line'])->build();
            }
        }

        return \array_values($errors);
    }
}
