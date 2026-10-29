<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Type\Type;
use PHPStan\Rules\Rule;
use PHPStan\Analyser\Scope;
use PHPStan\File\FileHelper;
use Noldors\PHPStan\Analysis\Calls;
use PHPStan\Rules\RuleErrorBuilder;
use Noldors\PHPStan\Eloquent\Arguments;
use PHPStan\Reflection\ReflectionProvider;
use Noldors\PHPStan\Analysis\ExcludedPaths;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Constant\ConstantIntegerType;

/**
 * @implements Rule<Node\Expr>
 */
final class DisabledTlsVerificationRule implements Rule
{
    private readonly Calls $calls;

    private readonly ExcludedPaths $excludedPaths;

    /**
     * @param list<string> $excludePaths
     */
    public function __construct(
        ReflectionProvider $reflectionProvider,
        FileHelper $fileHelper,
        array $excludePaths = [],
        private readonly bool $laravelClients = false,
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
        if ($this->excludedPaths->contains($scope->getFile()) || ! $this->disablesVerification($node, $scope)) {
            return [];
        }

        return [RuleErrorBuilder::message('Do not disable TLS certificate or hostname verification; configure a trusted CA when required.')
            ->identifier('security.disabledTlsVerification')->build()];
    }

    private function disablesVerification(Node\Expr $node, Scope $scope): bool
    {
        if ($node instanceof Node\Expr\FuncCall) {
            return ! $node->isFirstClassCallable() && $this->nativeOptions($node, $scope);
        }

        if ($node instanceof Node\Expr\New_) {
            return $this->calls->onClass($node, $scope, \GuzzleHttp\Client::class)
                && $this->insecureGuzzleOptions(Arguments::type($node->getArgs(), $scope, 'config', 0));
        }

        if (! $node instanceof Node\Expr\MethodCall && ! $node instanceof Node\Expr\NullsafeMethodCall && ! $node instanceof Node\Expr\StaticCall) {
            return false;
        }

        if ($node->isFirstClassCallable()) {
            return false;
        }
        $method = $this->calls->methodName($node, $scope);

        if ($this->laravelClients && $this->isLaravelClient($node, $scope)) {
            return 'withoutverifying' === $method || ('withoptions' === $method
                && $this->insecureGuzzleOptions(Arguments::type($node->getArgs(), $scope, 'options', 0)));
        }

        return $this->calls->onClass($node, $scope, \GuzzleHttp\ClientInterface::class)
            && $this->guzzleOptions($node, $scope, $method);
    }

    private function isLaravelClient(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $node, Scope $scope): bool
    {
        foreach (['Illuminate\\Http\\Client\\PendingRequest', 'Illuminate\\Http\\Client\\Factory', \Illuminate\Support\Facades\Http::class] as $class) {
            if ($this->calls->onClass($node, $scope, $class)) {
                return true;
            }
        }

        return false;
    }

    private function guzzleOptions(Node\Expr\MethodCall|Node\Expr\NullsafeMethodCall|Node\Expr\StaticCall $node, Scope $scope, string|null $method): bool
    {
        if (\in_array($method, ['request', 'requestasync'], true)) {
            return $this->insecureGuzzleOptions(Arguments::type($node->getArgs(), $scope, 'options', 2));
        }

        if (\in_array($method, ['send', 'sendasync', 'get', 'head', 'post', 'put', 'patch', 'delete', 'options', 'getasync', 'headasync', 'postasync', 'putasync', 'patchasync', 'deleteasync', 'optionsasync'], true)) {
            return $this->insecureGuzzleOptions(Arguments::type($node->getArgs(), $scope, 'options', 1));
        }

        return false;
    }

    private function nativeOptions(Node\Expr\FuncCall $node, Scope $scope): bool
    {
        $name = $this->calls->functionName($node, $scope);
        $args = $node->getArgs();

        return match ($name) {
            'curl_setopt' => $this->insecureCurlOption(Arguments::type($args, $scope, 'option', 1), Arguments::type($args, $scope, 'value', 2)),
            'curl_setopt_array' => $this->curlArray(Arguments::type($args, $scope, 'options', 1)),
            'stream_context_create', 'stream_context_set_options' => $this->sslArray(Arguments::type($args, $scope, 'options', 'stream_context_create' === $name ? 0 : 1)),
            'stream_context_set_option' => $this->streamOption($node, $scope),
            default => false,
        };
    }

    private function streamOption(Node\Expr\FuncCall $node, Scope $scope): bool
    {
        $args = $node->getArgs();

        if (\count($args) === 2) {
            return $this->sslArray(Arguments::type($args, $scope, 'wrapper_or_options', 1));
        }

        return Arguments::type($args, $scope, 'wrapper_or_options', 1)?->equals(new ConstantStringType('ssl')) === true
            && \in_array(Arguments::string(Arguments::type($args, $scope, 'option_name', 2)), ['verify_peer', 'verify_peer_name'], true)
            && $this->disabled(Arguments::type($args, $scope, 'value', 3));
    }

    private function sslArray(Type|null $type): bool
    {
        $ssl = new ConstantStringType('ssl');

        if (null === $type || ! $type->hasOffsetValueType($ssl)->yes()) {
            return false;
        }
        $options = $type->getOffsetValueType($ssl);

        foreach (['verify_peer', 'verify_peer_name'] as $name) {
            $key = new ConstantStringType($name);

            if ($options->hasOffsetValueType($key)->yes() && $this->disabled($options->getOffsetValueType($key))) {
                return true;
            }
        }

        return false;
    }

    private function insecureGuzzleOptions(Type|null $type): bool
    {
        $key = new ConstantStringType('verify');

        return null !== $type && $type->hasOffsetValueType($key)->yes() && $type->getOffsetValueType($key)->isFalse()->yes();
    }

    private function curlArray(Type|null $type): bool
    {
        foreach ([64, 81] as $option) {
            $key = new ConstantIntegerType($option);

            if (null !== $type && $type->hasOffsetValueType($key)->yes() && $this->zeroCurlFlag($type->getOffsetValueType($key))) {
                return true;
            }
        }

        return false;
    }

    private function insecureCurlOption(Type|null $option, Type|null $value): bool
    {
        if (! $option instanceof ConstantIntegerType || ! \in_array($option->getValue(), [64, 81], true)) {
            return false;
        }

        return $this->zeroCurlFlag($value);
    }

    private function zeroCurlFlag(Type|null $type): bool
    {
        return null !== $type && $type->toInteger()->equals(new ConstantIntegerType(0));
    }

    private function disabled(Type|null $type): bool
    {
        return null !== $type && $type->toBoolean()->isFalse()->yes();
    }
}
