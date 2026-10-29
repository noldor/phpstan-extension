<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use Noldors\PHPStan\Eloquent\Arguments;

final class FluentBuilderRules
{
    private const array STRING = [
        'alpha' => ['alpha'], 'alphadash' => ['alpha_dash'], 'alphanumeric' => ['alpha_num'], 'ascii' => ['ascii'],
        'between' => ['between'], 'doesntendwith' => ['doesnt_end_with'], 'doesntstartwith' => ['doesnt_start_with'],
        'endswith' => ['ends_with'], 'exactly' => ['size'], 'lowercase' => ['lowercase'], 'max' => ['max'],
        'min' => ['min'], 'startswith' => ['starts_with'], 'uppercase' => ['uppercase'],
    ];

    private const array NUMERIC = [
        'between' => ['between'], 'decimal' => ['decimal'], 'different' => ['different'], 'digits' => ['digits'],
        'digitsbetween' => ['digits_between'], 'greaterthan' => ['gt'], 'greaterthanorequalto' => ['gte'],
        'integer' => ['integer'], 'lessthan' => ['lt'], 'lessthanorequalto' => ['lte'], 'max' => ['max'],
        'maxdigits' => ['max_digits'], 'min' => ['min'], 'mindigits' => ['min_digits'],
        'multipleof' => ['multiple_of'], 'same' => ['same'], 'exactly' => ['size'],
    ];

    private const array DATE = [
        'beforetoday' => ['before'], 'aftertoday' => ['after'], 'todayorbefore' => ['before_or_equal'],
        'todayorafter' => ['after_or_equal'], 'past' => ['before'], 'future' => ['after'],
        'noworpast' => ['before_or_equal'], 'noworfuture' => ['after_or_equal'], 'before' => ['before'],
        'after' => ['after'], 'beforeorequal' => ['before_or_equal'], 'afterorequal' => ['after_or_equal'],
        'between' => ['after', 'before'], 'betweenorequal' => ['after_or_equal', 'before_or_equal'],
        'equals' => ['date_equals'],
    ];

    /**
     * @param list<array{method: string, arguments: list<Node\Arg>}> $calls
     */
    public function read(string $class, string $factory, array $calls, Scope $scope): RuleSet
    {
        $mapping = match ($class) {
            'StringRule' => self::STRING, 'Numeric' => self::NUMERIC, default => self::DATE
        };
        $base = match ($class) {
            'StringRule' => 'string', 'Numeric' => 'numeric', default => 'datetime' === $factory ? 'date_format' : 'date'
        };
        $rules = [];
        $unknown = false;

        foreach ($calls as $call) {
            if ('Date' === $class && 'format' === $call['method']) {
                $type = Arguments::type($call['arguments'], $scope, 'format', 0);
                $base = null !== $type && $type->isNull()->yes() ? 'date' : 'date_format';

                continue;
            }

            if (! isset($mapping[$call['method']])) {
                $unknown = true;

                continue;
            }
            $rules = [...$rules, ...$mapping[$call['method']]];
        }

        return new RuleSet([$base, ...$rules], unknown: $unknown);
    }
}
