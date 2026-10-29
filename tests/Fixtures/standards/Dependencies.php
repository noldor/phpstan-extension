<?php

namespace StandardsFixture\Dependencies;

interface Repository { public function read(): void; }

final class NeedsDeclarations
{
    private Repository $repository; // @expect api.dependencyPromotion api.dependencyReadonly
    public function __construct(Repository $repository) { $this->repository = $repository; }
    public function read(): void { $this->repository->read(); }
}

final class NeedsReadonly
{
    public function __construct(private Repository $repository) {} // @expect api.dependencyReadonly
}

final class ValidDependency
{
    public function __construct(private readonly Repository $repository) {}
    public function read(): void { $this->repository->read(); }
}

final class MutableDependency
{
    public function __construct(private Repository $repository) {}
    public function replace(Repository $repository): void { $this->repository = $repository; }
}

final class TransformedInput
{
    private Repository $repository;
    public function __construct(Repository $repository) { $this->repository = clone $repository; }
}

final class HydratedDependency
{
    public function __construct(private Repository $repository) {}
    public function __unserialize(array $data): void { $this->repository = $data['repository']; }
}
