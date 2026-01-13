<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Psr\Container\ContainerInterface;
use Strux\Foundation\App;

class AppRegistry extends ServiceRegistry
{
    protected ContainerInterface $container;

    /**
     * @var array<int, object>
     */
    protected array $registries = [];

    /**
     * Core registries that are always loaded.
     */
    protected array $coreRegistries = [
        LogRegistry::class,
        DatabaseRegistry::class,
        AuthRegistry::class,
        HttpRegistry::class,
        RouteRegistry::class,
        ViewRegistry::class,
        EventRegistry::class,
        MiddlewareRegistry::class,
        InfrastructureRegistry::class,
    ];

    /**
     * Build and Register Services (Bindings).
     */
    public function build(): void
    {
        // 1. Load Core Framework Registries
        foreach ($this->coreRegistries as $registryClass) {
            $this->instantiateAndBuild($registryClass);
        }
        // 2. Discover and Load User Registries
        $this->discoverUserRegistries();
    }

    /**
     * Initialize/Boot Services (After bindings are complete).
     *
     * @param App $app
     */
    public function init(App $app): void
    {
        /**@var ServiceRegistry $registry */
        foreach ($this->registries as $registry) {
            if (method_exists($registry, 'init')) {
                $registry->init($app);
            }
        }
    }

    /**
     * Instantiate a registry class and call its build/register method.
     *
     * @param string $className
     */
    protected function instantiateAndBuild(string $className): void
    {
        if (!class_exists($className)) {
            return;
        }

        /** @var ServiceRegistry $registry */
        $registry = new $className($this->container);

        $this->registries[] = $registry;

        if (method_exists($registry, 'build')) {
            $registry->build();
        }
    }

    /**
     * Scan the App/Registry directory for user-defined registries.
     */
    protected function discoverUserRegistries(): void
    {
        // $registryDir = (defined('ROOT_PATH') ? ROOT_PATH : getcwd()) . '/src/Registry';
        $registryDir = !defined('ROOT_PATH')
            ? define('ROOT_PATH', getcwd() . '/src/Registry')
            : ROOT_PATH . '/src/Registry';

        if (!is_dir($registryDir)) {
            return;
        }

        $files = glob($registryDir . '/*.php');

        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $className = "App\\Registry\\{$filename}";

            $this->instantiateAndBuild($className);
        }
    }
}