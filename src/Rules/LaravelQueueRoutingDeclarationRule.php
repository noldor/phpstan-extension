<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Laravel\QueueRouting;

/**
 * @implements Rule<InClassNode>
 */
final class LaravelQueueRoutingDeclarationRule implements Rule
{
    public function __construct(
        private readonly QueueRouting $routing,
    ) {}

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        $class = $node->getClassReflection();
        $declaration = $node->getOriginalNode();
        $role = $this->routing->role($class);

        if (! $declaration instanceof Node\Stmt\Class_ || null === $role) {
            return [];
        }

        return [
            ...$this->queueErrors($class, $declaration, $role),
            ...$this->propertyErrors($class, $declaration),
            ...$this->promotedErrors($class, $declaration),
            ...$this->methodErrors($class, $declaration, $role),
        ];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function queueErrors(ClassReflection $class, Node\Stmt\Class_ $declaration, string $role): array
    {
        if ($class->isAbstract()) {
            return [];
        }

        if ('notification' === $role) {
            $line = $this->notificationViolation($class, $declaration);

            return null === $line ? [] : [RuleErrorBuilder::message('Queued application notifications must declare channel queues through a public viaQueues() method; cover every statically known channel or declare an internal fallback queue.')->identifier('laravel.notificationQueues')->line($line)->build()];
        }

        return $this->routing->hasQueue($class, $role) ? [] : [RuleErrorBuilder::message('Declare the application queue inside the job or queued listener; external routing cannot satisfy this contract.')->identifier('laravel.queueRoute')->build()];
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function propertyErrors(ClassReflection $class, Node\Stmt\Class_ $declaration): array
    {
        $errors = [];

        foreach ($declaration->getProperties() as $property) {
            if (! $property->isPublic() || $property->isStatic()) {
                continue;
            }

            foreach ($property->props as $item) {
                $name = $item->name->toString();

                if ($this->staticProperty($class, $name, $item->default)) {
                    $errors[] = RuleErrorBuilder::message('Declare static queue configuration with the native #[\\Illuminate\\Queue\\Attributes\\' . \ucfirst($name) . '] attribute; attributes do not initialize job properties.')->identifier('laravel.queueAttribute')->line($item->getStartLine())->build();
                }
            }
        }

        return $errors;
    }

    private function staticProperty(ClassReflection $class, string $name, Node\Expr|null $default): bool
    {
        return \in_array($name, ['queue', 'connection', 'tries', 'timeout', 'backoff'], true) && null !== $default && $this->routing->isConstant($default) && ! $this->routing->value($class, $default)->isNull()->yes() && $this->routing->supportsAttribute($name);
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function promotedErrors(ClassReflection $class, Node\Stmt\Class_ $declaration): array
    {
        $errors = [];

        foreach ($declaration->getMethod('__construct')->params ?? [] as $parameter) {
            if (! $parameter->var instanceof Node\Expr\Variable || ! \is_string($parameter->var->name) || 0 === ($parameter->flags & Node\Stmt\Class_::MODIFIER_PUBLIC)) {
                continue;
            }

            if ($this->staticProperty($class, $parameter->var->name, $parameter->default)) {
                $errors[] = RuleErrorBuilder::message('Declare static promoted queue configuration with the native queue attribute; attributes do not initialize job properties.')->identifier('laravel.queueAttribute')->line($parameter->getStartLine())->build();
            }
        }

        return $errors;
    }

    /**
     * @return list<\PHPStan\Rules\IdentifierRuleError>
     */
    private function methodErrors(ClassReflection $class, Node\Stmt\Class_ $declaration, string $role): array
    {
        $errors = [];
        $methods = ['tries' => 'tries', 'backoff' => 'backoff'];

        if ('listener' === $role) {
            $methods += ['viaQueue' => 'queue', 'viaConnection' => 'connection'];
        }

        foreach ($methods as $method => $attribute) {
            $own = $declaration->getMethod($method);

            $value = null !== $own ? $this->routing->methodValue($class, $method) : null;

            if (null !== $own && $own->isPublic() && ! $own->isStatic() && null !== $value && ! $value->isNull()->yes() && $this->routing->supportsAttribute($attribute)) {
                $errors[] = RuleErrorBuilder::message('Declare constant ' . $method . '() configuration with the native #[\\Illuminate\\Queue\\Attributes\\' . \ucfirst($attribute) . '] attribute; retain dynamic methods.')->identifier('laravel.queueAttribute')->line($own->getStartLine())->build();
            }
        }

        return $errors;
    }

    private function notificationViolation(ClassReflection $class, Node\Stmt\Class_ $declaration): int|null
    {
        if (! $this->routing->publicMethod($class, 'viaQueues')) {
            return $declaration->getStartLine();
        }
        $queues = $this->routing->methodValue($class, 'viaQueues');

        if (null === $queues) {
            return null;
        }
        $fallback = $this->routing->hasQueue($class, 'notification');
        $arrays = $queues->getConstantArrays();
        $line = $declaration->getMethod('viaQueues')?->getStartLine() ?? $declaration->getStartLine();

        if (1 !== \count($arrays)) {
            return $line;
        }

        if ([] === $arrays[0]->getKeyTypes()) {
            return $fallback ? null : $line;
        }
        $keys = [];

        foreach ($arrays[0]->getKeyTypes() as $index => $key) {
            $value = $arrays[0]->getValueTypes()[$index];

            if (! $this->routing->usableQueue($value) && ! ($fallback && $value->isNull()->yes())) {
                return $line;
            }

            foreach ($key->getConstantStrings() as $string) {
                $keys[] = $string->getValue();
            }
        }

        return ! $fallback && $this->missingChannels($class, $keys) ? $line : null;
    }

    /**
     * @param list<string> $keys
     */
    private function missingChannels(ClassReflection $class, array $keys): bool
    {
        $channels = $this->routing->publicMethod($class, 'via') ? $this->routing->methodValue($class, 'via') : null;

        foreach ($channels?->getConstantArrays() ?? [] as $array) {
            foreach ($array->getValueTypes() as $channel) {
                foreach ($channel->getConstantStrings() as $string) {
                    if (! \in_array($string->getValue(), $keys, true)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
