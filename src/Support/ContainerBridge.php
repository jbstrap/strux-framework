<?php

declare(strict_types=1);

namespace Strux\Support;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Strux\Component\Exceptions\Container\ContainerException;

/**
 * Class ContainerBridge
 *
 * Provides static access to core framework services, primarily the DI container.
 * This acts as a controlled service locator for global helper functions.
 */
class ContainerBridge
{
    private static ?ContainerInterface $container = null;

    /**
     * Sets the global container instance.
     * This should be called once during application bootstrap.
     *
     * @param ContainerInterface $containerInstance
     * @return void
     */
    public static function setContainer(ContainerInterface $containerInstance): void
    {
        self::$container = $containerInstance;
    }

    /**
     * Gets the global container instance.
     *
     * @return ContainerInterface
     * @throws ContainerException if the container has not been initialized.
     */
    public static function getContainer(): ContainerInterface
    {
        if (self::$container === null) {
            // This error indicates a setup problem in the application's entry point (e.g., web/index.php)
            throw new ContainerException("ContainerBridge: The DI container has not been initialized. Call ContainerBridge::setContainer() first.");
        }
        return self::$container;
    }

    /**
     * Resolves a service from the container by its ID.
     *
     * @param string $id The ID of the service to retrieve.
     * @return mixed The resolved service.
     * @throws NotFoundExceptionInterface If the service is not found.
     * @throws ContainerExceptionInterface If any other error occurs during resolution.
     * @throws ContainerException if the container has not been initialized.
     */
    public static function resolve(string $id): mixed
    {
        return self::getContainer()->get($id);
    }

    /**
     * Checks if a service is registered in the container.
     *
     * @param string $id The ID of the service.
     * @return bool True if the service exists, false otherwise.
     * @throws ContainerException if the container has not been initialized.
     */
    public static function has(string $id): bool
    {
        return self::getContainer()->has($id);
    }
}