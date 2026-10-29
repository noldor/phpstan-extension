<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use Closure;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use Noldors\PHPStan\Eloquent\Arguments;

final class FileBuilderRules
{
    /**
     * @param list<Node\Arg> $arguments
     * @param list<array{method: string, arguments: list<Node\Arg>}> $calls
     * @param Closure(Node\Expr): RuleSet $read
     */
    public function read(string $class, string $factory, array $arguments, array $calls, Scope $scope, Closure $read): RuleSet
    {
        $rules = new RuleSet(['file']);
        $custom = new RuleSet('ImageFile' === $class ? ['image'] : []);
        $encoding = new RuleSet();
        $extensions = new RuleSet();
        $minimum = null;
        $maximum = null;

        if ('types' === $factory) {
            $rules = $rules->append($this->mimetypes($arguments, $scope));
        }

        foreach ($calls as $call) {
            if (\in_array($call['method'], ['min', 'max', 'size', 'between'], true)) {
                [$minimum, $maximum] = $this->bounds($minimum, $maximum, $call, $scope);

                continue;
            }
            $addition = $this->constraint($call, $scope, $read);

            if ('encoding' === $call['method']) {
                $encoding = $addition;
            } elseif ('extensions' === $call['method']) {
                $extensions = $addition;
            } elseif (\in_array($call['method'], ['rules', 'dimensions'], true)) {
                $custom = $custom->append($addition);
            } else {
                $rules = $rules->append($addition);
            }
        }
        $size = $this->size($minimum, $maximum);

        return new RuleSet(nested: [$rules->append($extensions)->append(new RuleSet($size))->append($encoding)->append($custom)]);
    }

    /**
     * @param list<Node\Arg> $arguments
     */
    private function mimetypes(array $arguments, Scope $scope): RuleSet
    {
        $types = Arguments::strings(Arguments::type($arguments, $scope, 'mimetypes', 0));
        $mimetypes = false;
        $extensions = false;

        foreach ($types as $type) {
            $mimetypes = $mimetypes || \str_contains($type, '/');
            $extensions = $extensions || ! \str_contains($type, '/');
        }

        return new RuleSet([...($mimetypes ? ['mimetypes'] : []), ...($extensions ? ['mimes'] : [])]);
    }

    /**
     * @param array{method: string, arguments: list<Node\Arg>} $call
     * @param Closure(Node\Expr): RuleSet $read
     */
    private function constraint(array $call, Scope $scope, Closure $read): RuleSet
    {
        if (\in_array($call['method'], ['rules', 'dimensions'], true)) {
            $expression = Arguments::expression($call['arguments'], $call['method'], 0);

            return null === $expression ? new RuleSet(unknown: true) : $read($expression);
        }

        if (\in_array($call['method'], ['extensions', 'encoding'], true)) {
            $type = Arguments::type($call['arguments'], $scope, $call['method'], 0);

            if (null !== $type && $type->toBoolean()->isFalse()->yes()) {
                return new RuleSet();
            }

            return new RuleSet([$call['method']]);
        }

        return new RuleSet(unknown: true);
    }

    /**
     * @param array{method: string, arguments: list<Node\Arg>} $call
     *
     * @return array{int|float|false|null, int|float|false|null}
     */
    private function bounds(false|float|int|null $minimum, false|float|int|null $maximum, array $call, Scope $scope): array
    {
        $first = $this->bound($call['arguments'], $scope, 'between' === $call['method'] ? 'minSize' : 'size', 0);
        $second = $this->bound($call['arguments'], $scope, 'maxSize', 1);

        return match ($call['method']) {
            'min' => [$first, $maximum], 'max' => [$minimum, $first], 'size' => [$first, $first],
            default => [$first, $second],
        };
    }

    /**
     * @param list<Node\Arg> $arguments
     */
    private function bound(array $arguments, Scope $scope, string $name, int $position): false|float|int|null
    {
        $type = Arguments::type($arguments, $scope, $name, $position);

        if (null === $type || $type->isNull()->yes()) {
            return null;
        }
        $values = $type->getConstantScalarValues();

        if (1 !== \count($values) || \is_bool($values[0])) {
            return false;
        }

        if (\is_string($values[0])) {
            return $this->kilobytes($values[0]);
        }

        return $values[0];
    }

    private function kilobytes(string $size): false|float
    {
        if (1 !== \preg_match('/^([+-]?(?:[0-9]+(?:\\.[0-9]*)?|\\.[0-9]+))\\s*(kb|mb|gb|tb)$/i', \mb_trim($size), $matches)) {
            return false;
        }
        $factor = match (\mb_strtolower($matches[2])) {
            'kb' => 1, 'mb' => 1_000, 'gb' => 1_000_000, default => 1_000_000_000
        };

        return \round((float) $matches[1] * $factor);
    }

    /**
     * @return list<string>
     */
    private function size(false|float|int|null $minimum, false|float|int|null $maximum): array
    {
        return match (true) {
            null === $minimum && null === $maximum => [], null === $minimum => ['max'],
            null === $maximum => ['min'], false === $minimum || false === $maximum => [],
            $minimum === $maximum => ['size'], default => ['between'],
        };
    }
}
