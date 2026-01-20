<?php

namespace Strux\Foundation;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Dotenv\Exception\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Strux\Bootstrapping\Registry\AppRegistry;
use Strux\Component\Config\Config;
use Strux\Support\ContainerBridge;

/**
 * Class Kernel
 *
 * The main entry point for creating and bootstrapping a Kernel application.
 */
class Kernel
{
    /**
     * Create and bootstrap the application.
     *
     * @param string $rootPath The root path of the application.
     * @param string|null $appClass The Application class to instantiate (defaults to Strux\Foundation\Application).
     * @return Application The bootstrapped application instance.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface|ReflectionException
     */
    public static function create(string $rootPath, ?string $appClass = null): Application
    {
        /**
         * -------------------------------------------------------------------------
         * Load Environment Variables
         * -------------------------------------------------------------------------
         */
        if (class_exists(Dotenv::class) && file_exists($rootPath . '/.env')) {
            try {
                $dotenv = Dotenv::createImmutable($rootPath);
                $dotenv->load();
            } catch (InvalidPathException|ValidationException $e) {
                error_log("FATAL: Dotenv Exception: " . $e->getMessage());
                http_response_code(500);
                die("<h1>Application Configuration Error</h1><p>Essential configuration failed to load.</p>");
            }
        }

        /**
         * -------------------------------------------------------------------------
         * Create The Application Container
         * -------------------------------------------------------------------------
         */
        $container = new Container();
        $container->singleton(ContainerInterface::class, $container);
        ContainerBridge::setContainer($container);

        /**
         * -------------------------------------------------------------------------
         * Register Core Configuration
         * -------------------------------------------------------------------------
         */
        $configValues = [];
        if (file_exists($rootPath . '/etc/config.php')) {
            $configValues = require $rootPath . '/etc/config.php';
        }

        if (!is_dir($rootPath . '/vendor')) {
            throw new \RuntimeException("Vendor directory not found. Please run 'composer install'.");
        }

        $container->singleton(Config::class, fn() => new Config($configValues, $rootPath));

        /**
         * -------------------------------------------------------------------------
         * Kernel The Framework
         * -------------------------------------------------------------------------
         */
        $framework = new AppRegistry($container);
        $framework->build();

        /**
         * -------------------------------------------------------------------------
         * Create & Initialize The Application
         * -------------------------------------------------------------------------
         */
        $appClassName = $appClass ?? Application::class;

        if (!class_exists($appClassName)) {
            throw new \RuntimeException("Application class '$appClassName' not found.");
        }

        /** @var Application $app */
        $app = new $appClassName($container, $rootPath);
        $framework->init($app);

        return $app;
    }
}