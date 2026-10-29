<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Validation;

final class RuleSet
{
    /**
     * @param list<string> $rules
     * @param list<self> $nested
     */
    public function __construct(
        public readonly array $rules = [],
        public readonly array $nested = [],
        public readonly bool $unknown = false,
    ) {}

    public function append(self $other): self
    {
        return new self([...$this->rules, ...$other->rules], [...$this->nested, ...$other->nested], $this->unknown || $other->unknown);
    }
}
