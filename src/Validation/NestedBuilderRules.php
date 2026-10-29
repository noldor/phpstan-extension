<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use Closure;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use Noldors\PHPStan\Eloquent\Arguments;

final class NestedBuilderRules
{
    private const array PASSWORD = [
        'mixedcase' => 'password.mixed', 'letters' => 'password.letters',
        'numbers' => 'password.numbers', 'symbols' => 'password.symbols', 'uncompromised' => 'password.uncompromised',
    ];

    /**
     * @param list<array{method: string, arguments: list<Node\Arg>}> $calls
     * @param Closure(Node\Expr): RuleSet $read
     */
    public function read(string $class, string $factory, array $calls, Scope $scope, Closure $read): RuleSet
    {
        if (\in_array($factory, ['default', 'defaults', 'required', 'sometimes'], true)) {
            return new RuleSet(unknown: true);
        }
        $rules = new RuleSet('Password' === $class ? ['string', 'min'] : ['email']);
        $custom = new RuleSet();
        $maximum = new RuleSet();
        $complexity = new RuleSet();

        foreach ($calls as $call) {
            if ('rules' === $call['method']) {
                $expression = Arguments::expression($call['arguments'], 'rules', 0);
                $addition = null === $expression ? new RuleSet(unknown: true) : $read($expression);
                $custom = 'Password' === $class ? $addition : $custom->append($addition);

                continue;
            }

            if ('Password' === $class && 'max' === $call['method']) {
                $type = Arguments::type($call['arguments'], $scope, 'size', 0);
                $maximum = null !== $type && $type->toBoolean()->isFalse()->yes() ? new RuleSet() : new RuleSet(['max']);
            } elseif ('Password' === $class && isset(self::PASSWORD[$call['method']])) {
                $complexity = $complexity->append(new RuleSet([self::PASSWORD[$call['method']]]));
            }
        }

        return new RuleSet(nested: [$rules->append($maximum)->append($custom)->append($complexity)]);
    }
}
