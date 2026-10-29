<?php

declare(strict_types = 1);

namespace Noldors\PHPStan\Schema;

final class ForeignKeyBlueprint
{
    /**
     * @var array<string, ForeignKeyColumn>
     */
    public array $columns = [];
}
