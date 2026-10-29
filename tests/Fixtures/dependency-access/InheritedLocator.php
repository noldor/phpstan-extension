<?php

namespace DependencyAccess;

use Psr\Container\ContainerInterface;

class LookupBase
{
    public function get(string $id): mixed { return null; }
    public function has(string $id): bool { return false; }
}

final class PsrLocator extends LookupBase implements ContainerInterface {}

final class LookupService
{
    public function read(PsrLocator $container, ContainerInterface $interface): void
    {
        $container->get('service');
        $container?->get('service');
        $lookup = $container->get(...);
        $interface->get('service');
        new LookupBase()->get('allowed');
    }
}
