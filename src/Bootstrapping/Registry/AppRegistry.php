<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Psr\Container\ContainerInterface;
use ReflectionClass;
use ReflectionException;
use Strux\Foundation\Application;
use Strux\Support\ContainerBridge;

class AppRegistry extends ServiceRegistry
{
    protected ?ContainerInterface $container;

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

    public function __construct(?ContainerInterface $container)
    {
        $this->container = $container ?? ContainerBridge::get(ContainerInterface::class);
        parent::__construct($container);
    }

    /**
     * Build and Register Services (Bindings).
     * @throws ReflectionException
     */
    public function build(): void
    {
        // 1. Load Core Registries
        foreach ($this->coreRegistries as $registryClass) {
            $this->instantiateAndBuild($registryClass);
        }

        // 2. Auto-discover User Registries (Application\Registry\*)
        $this->discoverUserRegistries();
    }

    /**
     * Initialize/Boot Services (After bindings are complete).
     *
     * @param Application $app
     */
    public function init(Application $app): void
    {
        foreach ($this->registries as $registry) {
            if (method_exists($registry, 'init')) {
                // Pass the Application instance if the method expects it, otherwise call without args
                $registry->init($app);
            } elseif (method_exists($registry, 'boot')) {
                // Support 'boot' as an alternative naming convention
                $registry->boot($app);
            }
        }
    }

    /**
     * Register a registry instance or class name.
     *
     * @param string|object $registry
     * @throws ReflectionException
     */
    protected function instantiateAndBuild(string|object $registry): void
    {
        // Case 1: It's a class string (Standard Registry)
        if (is_string($registry)) {
            if (!class_exists($registry)) {
                return;
            }
            // Instantiate with Container
            $registry = new $registry($this->container);
        } // Case 2: It's an Object (Anonymous Registry)
        elseif (is_object($registry)) {
            // If the anonymous class was instantiated outside (via return new class...),
            // it might not have the container set yet. We inject it manually here.
            $this->injectContainer($registry);
        }

        // Store for the init/boot phase
        $this->registries[] = $registry;

        // Execute Binding Logic
        if (method_exists($registry, 'build')) {
            $registry->build();
        } elseif (method_exists($registry, 'register')) {
            $registry->register();
        }
    }

    /**
     * Helper to inject container into an instantiated registry object.
     * Essential for anonymous classes which bypass the __construct($container) call.
     * @throws ReflectionException
     */
    protected function injectContainer(object $registry): void
    {
        // If the object has a 'container' property, try to set it.
        if (property_exists($registry, 'container')) {
            $reflection = new ReflectionClass($registry);
            $property = $reflection->getProperty('container');

            // Allow access to protected/private properties
            if (!$property->isPublic()) {
                $property->setAccessible(true);
            }

            // Only set if not already set (optional safety)
            if (!$property->isInitialized($registry) || $property->getValue($registry) === null) {
                $property->setValue($registry, $this->container);
            }
        }
    }

    /**
     * Scan the Application/Registry directory for user-defined registries.
     * @throws ReflectionException
     */
    protected function discoverUserRegistries(): void
    {
        // Assume standard structure: ROOT_PATH/src/Registry maps to Application\Registry
        $registryDir = (defined('ROOT_PATH') ? ROOT_PATH : getcwd()) . '/src/Registry';

        if (!is_dir($registryDir)) {
            return;
        }

        $files = glob($registryDir . '/*.php');

        foreach ($files as $file) {
            // 1. Try to include the file and capture the return value
            // This supports: return new class extends ServiceRegistry { ... };
            $returned = include_once $file;

            if (is_object($returned)) {
                $this->instantiateAndBuild($returned);
                continue; // It was an anonymous class, we are done with this file
            }

            // 2. Fallback to Class Name inference (Standard named classes)
            // This supports: class AppRegistry extends ServiceRegistry { ... }
            $filename = basename($file, '.php');
            $className = "Application\\Registry\\{$filename}";

            // Check if class exists (it should, since we just included the file)
            if (class_exists($className)) {
                $this->instantiateAndBuild($className);
            }
        }
    }
}