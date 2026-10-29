<?php

declare(strict_types = 1);

namespace DependencyAccess;

use Illuminate\Http\Request;

final class PhpDocUseCase
{
    /** @var list<Request> */
    public array $requests = [];

    /** @param list<Request> $requests */
    public function handle(array $requests): void {}
}
