<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Eloquent;

final readonly class PivotState
{
    public function __construct(
        public string|null $related,
        public bool $morph,
        public string|null $pivot = null,
        public string|null $declaringInverse = null,
        public string|null $relatedInverse = null,
    ) {}

    public function using(string|null $pivot): self
    {
        return new self($this->related, $this->morph, $pivot, $this->declaringInverse, $this->relatedInverse);
    }

    public function chaperone(string|null $declaring, string|null $related): self
    {
        return null === $this->pivot ? $this : new self($this->related, $this->morph, $this->pivot, $declaring, $related);
    }

    public function withoutChaperone(): self
    {
        return new self($this->related, $this->morph, $this->pivot);
    }
}
