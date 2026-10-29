<?php

namespace StandardsFixture\Dependencies;

final class UnsetDependency
{
    public function __construct(private Repository $repository) {}
    public function reset(): void { unset($this->repository); }
}

final class ReferencedDependency
{
    public function __construct(private Repository $repository) {}
    public function &reference(): Repository { return $this->repository; }
}

final class ParameterReference
{
    public function __construct(private Repository &$repository) {}
}

final class DynamicDependency
{
    public function __construct(private Repository $repository) {}
    public function replace(string $name, Repository $value): void { $this->{$name} = $value; }
}

final class MutableObject
{
    public function __construct(private readonly Repository $repository) {}
    public function read(): void { $this->repository->read(); }
}

final class ArrowReference
{
    public function __construct(private Repository $repository) {}
    public function reference(): \Closure { return fn &() => $this->repository; }
}
