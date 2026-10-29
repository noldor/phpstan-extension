<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Schema;

final class ForeignKeyColumn
{
    public function __construct(
        public readonly ForeignKeyBlueprint $blueprint,
        public readonly string|null $name,
        public readonly bool $supportsForeignKey,
        public bool|null $nullable = false,
        public bool $explicitTable = false,
    ) {}
}
