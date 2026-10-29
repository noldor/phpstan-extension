<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Laravel;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ClassReflection;
use Noldors\PHPStan\Eloquent\MethodSource;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\Configuration\ClassScope;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;

final class QueueRouting
{
    public function __construct(
        private readonly QueuePolicy $policy,
        private readonly ClassScope $listeners,
        private readonly ClassScope $notifications,
        private readonly ReflectionProvider $reflection,
        private readonly InitializerExprTypeResolver $initializers,
        private readonly MethodSource $methods,
    ) {}

    public function role(ClassReflection $class): string|null
    {
        if ($this->policy->isObserver($class)) {
            return null;
        }

        if ($this->policy->isJob($class)) {
            return 'job';
        }

        if (! $class->is(\Illuminate\Contracts\Queue\ShouldQueue::class)) {
            return null;
        }

        if ($this->notifications->contains($class)) {
            return 'notification';
        }

        return $this->listeners->contains($class) ? 'listener' : null;
    }

    public function selected(Type $type): bool
    {
        foreach ($type->getObjectClassReflections() as $class) {
            if (null !== $this->role($class)) {
                return true;
            }
        }

        foreach ($type->getConstantStrings() as $string) {
            if ($this->reflection->hasClass($string->getValue()) && null !== $this->role($this->reflection->getClass($string->getValue()))) {
                return true;
            }
        }

        return false;
    }

    public function nativeMethod(ClassReflection $class, string $owner, string $method): bool
    {
        if (! $class->hasNativeMethod($method) || ! $this->reflection->hasClass($owner) || ! $this->reflection->getClass($owner)->hasNativeMethod($method)) {
            return false;
        }
        $actual = $class->getNativeReflection()->getMethod($method);
        $expected = $this->reflection->getClass($owner)->getNativeReflection()->getMethod($method);

        return $actual->getFileName() === $expected->getFileName() && $actual->getStartLine() === $expected->getStartLine();
    }

    public function attribute(ClassReflection $class, string $name): Type|null
    {
        foreach ([$class, ...$class->getParents()] as $ancestor) {
            foreach ([$ancestor, ...$ancestor->getTraits()] as $source) {
                foreach ($source->getAttributes() as $attribute) {
                    if ($attribute->getName() === 'Illuminate\\Queue\\Attributes\\' . \ucfirst($name)) {
                        return $attribute->getArgumentTypes()[$name] ?? null;
                    }
                }
            }
        }

        return null;
    }

    public function supportsAttribute(string $property): bool
    {
        return $this->reflection->hasClass('Illuminate\\Queue\\Attributes\\' . \ucfirst($property));
    }

    public function hasQueue(ClassReflection $class, string $role): bool
    {
        if ('listener' === $role && $this->publicMethod($class, 'viaQueue')) {
            $value = $this->methodValue($class, 'viaQueue');

            return null === $value || $this->usableQueue($value);
        }
        $declared = $this->declaredQueue($class);

        if ('listener' === $role || ! $class->hasConstructor()) {
            return $declared;
        }
        $constructor = $this->methods->find($class, '__construct');

        if (null === $constructor) {
            return $declared;
        }

        return new QueueConstructorFlow()->routes(\array_values($constructor->stmts ?? []), $declared || $this->promotedQueue($constructor), fn (Node\Expr $expression): bool|null => $this->routeEffect($class, $expression));
    }

    public function publicMethod(ClassReflection $class, string $method): bool
    {
        return $class->hasNativeMethod($method) && $class->getNativeMethod($method)->isPublic() && ! $class->getNativeMethod($method)->isStatic();
    }

    public function methodValue(ClassReflection $class, string $method): Type|null
    {
        $definition = $this->methods->find($class, $method);

        if (null === $definition || 1 !== \count($definition->stmts ?? []) || ! $definition->stmts[0] instanceof Node\Stmt\Return_ || null === $definition->stmts[0]->expr) {
            return null;
        }
        $expression = $definition->stmts[0]->expr;

        return $this->isConstant($expression) ? $this->value($class->getNativeMethod($method)->getDeclaringClass(), $expression) : null;
    }

    public function isConstant(Node\Expr $expression): bool
    {
        if ($expression instanceof Node\Scalar\String_ || $expression instanceof Node\Scalar\Int_ || $expression instanceof Node\Scalar\Float_ || $expression instanceof Node\Expr\ConstFetch || ($expression instanceof Node\Expr\ClassConstFetch && $expression->class instanceof Node\Name)) {
            return true;
        }

        if ($expression instanceof Node\Expr\BinaryOp) {
            return $this->isConstant($expression->left) && $this->isConstant($expression->right);
        }

        if ($expression instanceof Node\Expr\UnaryMinus || $expression instanceof Node\Expr\UnaryPlus) {
            return $this->isConstant($expression->expr);
        }

        if (! $expression instanceof Node\Expr\Array_) {
            return false;
        }

        return $this->constantArray($expression);
    }

    public function value(ClassReflection $class, Node\Expr $expression): Type
    {
        return $this->initializers->getType($expression, InitializerExprContext::fromClassReflection($class));
    }

    public function usableQueue(Type $type): bool
    {
        if ($type->isNull()->yes()) {
            return false;
        }

        foreach ($type->getConstantStrings() as $string) {
            if ('' === \mb_trim($string->getValue())) {
                return false;
            }
        }

        return true;
    }

    public function isQueueApi(Node\Expr\MethodCall|Node\Expr\StaticCall $call, Scope $scope, string $method): bool
    {
        if ($call instanceof Node\Expr\StaticCall && $call->class instanceof Node\Name) {
            $name = $scope->resolveName($call->class);

            return $this->reflection->hasClass($name) && $this->reflection->getClass($name)->is(\Illuminate\Support\Facades\Queue::class) && ! $this->reflection->getClass($name)->hasNativeMethod($method);
        }

        if (! $call instanceof Node\Expr\MethodCall) {
            return false;
        }

        foreach ($scope->getType($call->var)->getObjectClassReflections() as $class) {
            if ($this->instanceQueueApi($class, $method)) {
                return true;
            }
        }

        return false;
    }

    public function argument(Node\Expr\CallLike $call, int $position, string $name): Node\Expr|null
    {
        foreach ($call->getArgs() as $index => $argument) {
            if ($argument->name?->toString() === $name || (null === $argument->name && $index === $position)) {
                return $argument->value;
            }
        }

        return null;
    }

    private function instanceQueueApi(ClassReflection $class, string $method): bool
    {
        if ($class->is(\Illuminate\Queue\QueueManager::class) && $this->nativeMethod($class, \Illuminate\Queue\QueueManager::class, '__call') && ! $class->hasNativeMethod($method) && $this->reflection->getClass(\Illuminate\Contracts\Queue\Queue::class)->hasNativeMethod($method)) {
            return true;
        }

        foreach ([\Illuminate\Queue\QueueRoutes::class, \Illuminate\Queue\QueueManager::class, \Illuminate\Queue\Queue::class, \Illuminate\Contracts\Queue\Queue::class] as $owner) {
            if ($class->is($owner) && $this->nativeMethod($class, $owner, $method)) {
                return true;
            }
        }

        return false;
    }

    private function declaredQueue(ClassReflection $class): bool
    {
        $attribute = $this->attribute($class, 'queue');

        if (null !== $attribute && ! $attribute->isNull()->yes()) {
            return $this->usableQueue($attribute);
        }

        if (! $class->hasNativeProperty('queue')) {
            return false;
        }
        $property = $class->getNativeProperty('queue');
        $expression = $class->getNativeReflection()->getProperty('queue')->getBetterReflection()->getDefaultValueExpression();

        return $property->isPublic() && ! $property->isStatic() && null !== $expression && $this->usableQueue($this->value($property->getDeclaringClass(), $expression));
    }

    private function constantArray(Node\Expr\Array_ $array): bool
    {
        foreach ($array->items as $item) {
            if (! $this->isConstant($item->value) || (null !== $item->key && ! $this->isConstant($item->key)) || $item->unpack) {
                return false;
            }
        }

        return true;
    }

    private function promotedQueue(Node\Stmt\ClassMethod $constructor): bool
    {
        foreach ($constructor->params as $parameter) {
            if ($parameter->var instanceof Node\Expr\Variable && 'queue' === $parameter->var->name && 0 !== ($parameter->flags & Node\Stmt\Class_::MODIFIER_PUBLIC)) {
                return true;
            }
        }

        return false;
    }

    private function routeEffect(ClassReflection $class, Node\Expr $expression): bool|null
    {
        if (! $this->readableQueueProperty($class)) {
            return null;
        }

        if ($expression instanceof Node\Expr\Assign && $expression->var instanceof Node\Expr\PropertyFetch && $expression->var->var instanceof Node\Expr\Variable && 'this' === $expression->var->var->name && $expression->var->name instanceof Node\Identifier && 'queue' === $expression->var->name->toString()) {
            return $this->queueExpression($class, $expression->expr);
        }

        if ($expression instanceof Node\Expr\MethodCall && $this->ownFluentReceiver($class, $expression->var) && $expression->name instanceof Node\Identifier && 0 === \strcasecmp($expression->name->toString(), 'onQueue') && $this->nativeMethod($class, \Illuminate\Bus\Queueable::class, 'onQueue')) {
            $argument = $this->argument($expression, 0, 'queue');

            return null !== $argument ? $this->queueExpression($class, $argument) : null;
        }

        return null;
    }

    private function readableQueueProperty(ClassReflection $class): bool
    {
        if ($class->hasNativeProperty('queue')) {
            return $class->getNativeProperty('queue')->isPublic() && ! $class->getNativeProperty('queue')->isStatic();
        }

        return $class->allowsDynamicProperties() && ! $class->hasNativeMethod('__set') && ! $class->hasNativeMethod('__get');
    }

    private function queueExpression(ClassReflection $class, Node\Expr $expression): bool
    {
        if (! $this->isConstant($expression)) {
            return true;
        }
        $value = $this->value($class, $expression);

        if ($value->isNull()->yes()) {
            $attribute = $this->attribute($class, 'queue');

            return null !== $attribute && $this->usableQueue($attribute);
        }

        return $this->usableQueue($value);
    }

    private function ownFluentReceiver(ClassReflection $class, Node\Expr $receiver): bool
    {
        if ($receiver instanceof Node\Expr\Variable) {
            return 'this' === $receiver->name;
        }

        if (! $receiver instanceof Node\Expr\MethodCall || ! $receiver->name instanceof Node\Identifier || ! \in_array(\mb_strtolower($receiver->name->toString()), ['onqueue', 'onconnection', 'ongroup', 'withdeduplicator', 'allonqueue', 'allonconnection', 'delay', 'withoutdelay', 'aftercommit', 'beforecommit', 'chain', 'through'], true)) {
            return false;
        }

        return $this->nativeMethod($class, \Illuminate\Bus\Queueable::class, $receiver->name->toString()) && $this->ownFluentReceiver($class, $receiver->var);
    }
}
