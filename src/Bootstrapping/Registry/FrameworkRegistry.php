<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Strux\Component\Config\Config;
use Strux\Foundation\App;
use Throwable;

class FrameworkRegistry extends ServiceRegistry
{
    /**
     * The ordered list of registries to load.
     * Using keys allows the user to override specific core registries via config.
     */
    protected array $registries = [];

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function build(): void
    {
        $this->resolveRegistries();

        foreach ($this->registries as $registryClass) {
            if (class_exists($registryClass)) {
                $registry = new $registryClass($this->container);
                try {
                    $registry->build();
                } catch (Throwable $e) {
                    throw new RuntimeException("FATAL: Error building registry $registryClass: " . $e->getMessage(), 0, $e);
                }
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function init(App $app): void
    {
        if (empty($this->registries)) {
            $this->resolveRegistries();
        }

        foreach ($this->registries as $registryClass) {
            if (class_exists($registryClass)) {
                $registry = new $registryClass($this->container);
                if (method_exists($registry, 'init')) {
                    try {
                        $registry->init($app);
                    } catch (Throwable $e) {
                        throw new RuntimeException("FATAL: Error initializing registry $registryClass: " . $e->getMessage(), 0, $e);
                    }
                }
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveRegistries(): void
    {
        $config = $this->container->get(Config::class);
        $userRegistries = $config->get('app.registries', []);

        $coreRegistries = [
            'log' => LogRegistry::class,
            'infrastructure' => InfrastructureRegistry::class,
            'http' => HttpRegistry::class,
            'database' => DatabaseRegistry::class,
            'view' => ViewRegistry::class,
            'route' => RouteRegistry::class,
            'middleware' => MiddlewareRegistry::class,
            'event' => EventRegistry::class,
            'auth' => AuthRegistry::class,
            'app' => AppRegistry::class,
        ];

        $this->registries = array_merge($coreRegistries, $userRegistries);
    }
}