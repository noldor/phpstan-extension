<?php

declare(strict_types = 1);

namespace Nuwave\Lighthouse\Validation;

/**
 * Test-only inheritance contract; validation is never executed by these tests.
 */
abstract class Validator
{
    /**
     * @return array<string, mixed>
     */
    abstract public function rules(): array;
}
