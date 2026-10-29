<?php

namespace DependencyAccess;

use Illuminate\Support\Facades\Request as GlobalRequest;

final class ReadGlobalActor
{
    public function handle(): void
    {
        \request()->user();
        \request()?->user();
        GlobalRequest::user();
        $lookup = GlobalRequest::user(...);
        $lookup = \request()->user(...);
    }
}
