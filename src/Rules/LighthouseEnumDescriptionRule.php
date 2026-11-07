<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\CollectedDataNode;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Collectors\GraphQLEnumDescriptionCollector;
use Noldors\PHPStan\Collectors\LighthouseEnumRegistrationCollector;

/**
 * @implements Rule<CollectedDataNode>
 */
final class LighthouseEnumDescriptionRule implements Rule
{
    public function getNodeType(): string
    {
        return CollectedDataNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $registered = [];

        foreach ($node->get(LighthouseEnumRegistrationCollector::class) as $registrations) {
            foreach ($registrations as $class) {
                $registered[$this->classKey($class)] = true;
            }
        }
        $errors = [];

        foreach ($node->get(GraphQLEnumDescriptionCollector::class) as $file => $enums) {
            foreach ($enums as $enum) {
                $class = $enum['class'];

                if (! isset($registered[$this->classKey($class)])) {
                    continue;
                }

                if (null !== $enum['missingEnumLine']) {
                    $errors[] = RuleErrorBuilder::message("GraphQL enum {$class} must declare #[GraphQL\\Type\\Definition\\Description].")
                        ->identifier('lighthouse.enumDescription')->file($file)->line($enum['missingEnumLine'])->build();
                }

                foreach ($enum['missingCases'] as $case => $line) {
                    $errors[] = RuleErrorBuilder::message("GraphQL enum case {$class}::{$case} must declare #[GraphQL\\Type\\Definition\\Description].")
                        ->identifier('lighthouse.enumCaseDescription')->file($file)->line($line)->build();
                }
            }
        }

        return $errors;
    }

    private function classKey(string $class): string
    {
        // PHP folds only ASCII letters in class names.
        return \strtr(\mb_ltrim($class, '\\'), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }
}
