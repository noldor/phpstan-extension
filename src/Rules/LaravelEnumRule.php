<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Configuration\ClassScope;

/**
 * @implements Rule<InClassNode>
 */
final class LaravelEnumRule implements Rule
{
    /**
     * @param list<string> $directories
     */
    public function __construct(
        private readonly ClassScope $enums,
        private readonly FileHelper $files,
        private readonly array $directories,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $declaration = $node->getOriginalNode();

        if (! $declaration instanceof Node\Stmt\Enum_ || ! $this->enums->contains($node->getClassReflection())) {
            return [];
        }
        $errors = [];

        if (null !== $declaration->name && ! \str_ends_with($declaration->name->toString(), 'Enum')) {
            $errors[] = RuleErrorBuilder::message('Suffix application enum names with Enum.')->identifier('laravel.enumName')->build();
        }

        if ([] !== $this->directories && ! $this->hasDomainDirectory($scope->getFile())) {
            $errors[] = RuleErrorBuilder::message('Place application enums in a domain directory below a configured enum root.')->identifier('laravel.enumLocation')->build();
        }

        foreach ($declaration->stmts as $statement) {
            if ($statement instanceof Node\Stmt\EnumCase && \preg_match('/^[A-Z][A-Z0-9]*(?:_[A-Z0-9]+)*$/D', $statement->name->toString()) !== 1) {
                $errors[] = RuleErrorBuilder::message('Use UPPER_SNAKE_CASE for application enum cases.')->identifier('laravel.enumCase')->line($statement->getStartLine())->build();
            }
        }

        return $errors;
    }

    private function hasDomainDirectory(string $file): bool
    {
        $file = $this->files->normalizePath($file, '/');

        foreach ($this->directories as $directory) {
            $root = \mb_rtrim($this->files->normalizePath($this->files->absolutizePath($directory), '/'), '/') . '/';

            if (\str_starts_with($file, $root) && \str_contains(\mb_substr($file, \mb_strlen($root)), '/')) {
                return true;
            }
        }

        return false;
    }
}
