<?php

declare(strict_types = 1);

namespace DependencyAccess;

final class ExcludedByPath
{
    public function run(): void { \app('service'); }
}
