<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\Type\NeverType;
use PHPStan\File\FileHelper;
use PHPStan\Type\ObjectType;
use PHPStan\Type\TypeCombinator;
use Noldors\PHPStan\Analysis\Calls;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Eloquent\Arguments;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\Analysis\ExcludedPaths;

/**
 * @implements Rule<Node\Expr>
 */
final class FullRequestLoggingRule implements Rule
{
    private readonly Calls $calls;

    private readonly ExcludedPaths $excludedPaths;

    /**
     * @param list<string> $loggerClasses
     * @param list<string> $requestClasses
     * @param list<array{class: string, methods: list<string>}> $requestMethods
     * @param list<string> $excludePaths
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        FileHelper $fileHelper,
        private readonly array $loggerClasses = [\Psr\Log\LoggerInterface::class, \Illuminate\Support\Facades\Log::class],
        private readonly array $requestClasses = ['Symfony\\Component\\HttpFoundation\\Request', \Psr\Http\Message\ServerRequestInterface::class],
        private readonly array $requestMethods = [
            ['class' => 'Illuminate\\Http\\Request', 'methods' => ['all', 'getContent', 'json', 'toArray']],
            ['class' => 'Symfony\\Component\\HttpFoundation\\Request', 'methods' => ['getContent', 'toArray', 'getPayload']],
            ['class' => \Psr\Http\Message\ServerRequestInterface::class, 'methods' => ['getParsedBody', 'getBody']],
        ],
        array $excludePaths = [],
    ) {
        $this->calls = new Calls($reflectionProvider);
        $this->excludedPaths = new ExcludedPaths($fileHelper, $excludePaths);
    }

    public function getNodeType(): string
    {
        return Node\Expr::class;
    }

    public function processNode(Node $node, Scope $scope): array
    {
        if ((! $node instanceof Node\Expr\MethodCall && ! $node instanceof Node\Expr\NullsafeMethodCall && ! $node instanceof Node\Expr\StaticCall)
            || $node->isFirstClassCallable() || $this->excludedPaths->contains($scope->getFile())) {
            return [];
        }
        $method = $this->calls->methodName($node, $scope);

        if (! \in_array($method, ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug', 'log'], true) || ! $this->isLogger($node, $scope)) {
            return [];
        }
        $message = Arguments::expression($node->getArgs(), 'message', 'log' === $method ? 1 : 0);
        $context = Arguments::expression($node->getArgs(), 'context', 'log' === $method ? 2 : 1);

        if (! $this->containsPayload($message, $scope) && ! $this->containsPayload($context, $scope)) {
            return [];
        }

        return [RuleErrorBuilder::message('Do not log an entire request or request body; select explicit non-sensitive fields.')
            ->identifier('logging.fullRequestPayload')->build()];
    }

    private function isLogger(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $node, Scope $scope): bool
    {
        foreach ($this->loggerClasses as $class) {
            if ($this->calls->onClass($node, $scope, $class)) {
                return true;
            }
        }

        return false;
    }

    private function containsPayload(Node\Expr|null $value, Scope $scope): bool
    {
        if (null === $value || $value instanceof Node\Expr\Closure || $value instanceof Node\Expr\ArrowFunction) {
            return false;
        }

        if ($value instanceof Node\Expr\Variable && \in_array($value->name, ['_POST', '_REQUEST'], true)) {
            return true;
        }

        if ($this->isRequestObject($value, $scope)) {
            return true;
        }

        if ($value instanceof Node\Expr\Array_) {
            return $this->arrayContainsPayload($value, $scope);
        }

        if ($value instanceof Node\Expr\MethodCall || $value instanceof Node\Expr\NullsafeMethodCall || $value instanceof Node\Expr\StaticCall) {
            return ! $value->isFirstClassCallable() && $this->isRequestPayload($value, $scope);
        }

        return false;
    }

    private function isRequestObject(Node\Expr $value, Scope $scope): bool
    {
        $type = TypeCombinator::removeNull($scope->getType($value));

        if ($type instanceof NeverType) {
            return false;
        }

        foreach ($this->requestClasses as $class) {
            if (new ObjectType($class)->isSuperTypeOf($type)->yes()) {
                return true;
            }
        }

        return false;
    }

    private function arrayContainsPayload(Node\Expr\Array_ $value, Scope $scope): bool
    {
        foreach ($value->items as $item) {
            if ($this->containsPayload($item->value, $scope)) {
                return true;
            }
        }

        return false;
    }

    private function isRequestPayload(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $value, Scope $scope): bool
    {
        $method = $this->calls->methodName($value, $scope);

        foreach ($this->requestMethods as $selector) {
            if ($this->calls->onClass($value, $scope, $selector['class']) && \in_array($method, \array_map('mb_strtolower', $selector['methods']), true)) {
                if (\in_array($method, ['all', 'json'], true)) {
                    return $this->readsFullInput($value, $scope, $method);
                }

                return true;
            }
        }

        return false;
    }

    private function readsFullInput(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $value, Scope $scope, string $method): bool
    {
        $selection = Arguments::type($value->getArgs(), $scope, 'all' === $method ? 'keys' : 'key', 0);

        if (null !== $selection) {
            return 'all' === $method ? $selection->toBoolean()->isFalse()->yes() : $selection->isNull()->yes();
        }

        foreach ($value->getArgs() as $argument) {
            if ($argument->unpack) {
                return false;
            }
        }

        return true;
    }
}
