<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ConstantTypeHelper;
use Illuminate\Database\Eloquent\Model;
use Noldors\PHPStan\Eloquent\Arguments;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Eloquent\ModelScope;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\Eloquent\DateCastCheck;
use Noldors\PHPStan\Eloquent\ModelConstructor;

/**
 * @implements Rule<InClassNode>
 */
final class EloquentDateFormatRule implements Rule
{
    public function __construct(
        private readonly ModelScope $models,
        private readonly string $databaseDateFormat,
        private readonly ModelConstructor $constructors,
        private readonly ReflectionProvider $reflectionProvider,
        private readonly DateCastCheck $casts,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();

        if (! $this->models->contains($class) || $class->isAbstract()) {
            return [];
        }

        $errors = $this->casts->errors(ConstantTypeHelper::getTypeFromValue($class->getNativeReflection()->getDefaultProperties()['casts'] ?? []), $class);

        foreach ($class->getAttributes() as $attribute) {
            if ('Illuminate\\Database\\Eloquent\\Attributes\\DateFormat' !== $attribute->getName()) {
                continue;
            }
            $format = Arguments::string($attribute->getArgumentTypes()['format'] ?? null);
            $defaults = $class->getNativeReflection()->getDefaultProperties();

            if ($format === $this->databaseDateFormat && null === ($defaults['dateFormat'] ?? null) && $this->usesNativeStorage($class) && ! $this->constructors->mutates($class, 'dateFormat')) {
                return $errors;
            }
        }

        return [...$errors, RuleErrorBuilder::message(\sprintf('Declare an effective #[DateFormat(%s)] explicitly on each application model and preserve native storage serialization.', \var_export($this->databaseDateFormat, true)))
            ->identifier('laravel.modelDateFormat')->build()];
    }

    private function usesNativeStorage(ClassReflection $class): bool
    {
        $base = $this->reflectionProvider->getClass(Model::class)->getNativeReflection();

        foreach (['getDateFormat', 'fromDateTime'] as $name) {
            $actual = $class->getNativeReflection()->getMethod($name);
            $expected = $base->getMethod($name);

            if ($actual->getFileName() !== $expected->getFileName() || $actual->getStartLine() !== $expected->getStartLine()) {
                return false;
            }
        }

        return true;
    }
}
