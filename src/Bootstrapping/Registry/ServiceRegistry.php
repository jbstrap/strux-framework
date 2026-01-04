<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Psr\Container\ContainerInterface;
use Strux\Foundation\App;

abstract class ServiceRegistry implements ServiceRegistryInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * All registries must define how they build their services.
     */
    abstract public function build(): void;

    /**
     * The init method is optional for registries.
     */
    public function init(App $app): void
    {
        // This method can be overridden by child registries if they need init logic.
    }
}