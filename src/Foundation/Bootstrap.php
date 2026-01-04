<?php

namespace Strux\Foundation;

use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Dotenv\Exception\ValidationException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Strux\Bootstrapping\Registry\FrameworkRegistry;
use Strux\Component\Config\Config;
use Strux\Support\ContainerBridge;

class Bootstrap
{
    /**
     * Create and bootstrap the application.
     *
     * @param string $rootPath The root path of the application.
     * @return App The bootstrapped application instance.
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function create(string $rootPath): App
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
        $configFile = $rootPath . '/etc/config.php';
        if (!file_exists($configFile)) {
            throw new \RuntimeException("Configuration file not found at: " . $configFile);
        }

        $configValues = require $configFile;
        $container->singleton(Config::class, fn() => new Config($configValues));

        /**
         * -------------------------------------------------------------------------
         * Bootstrap The Framework
         * -------------------------------------------------------------------------
         */
        $framework = new FrameworkRegistry($container);
        $framework->build();

        /**
         * -------------------------------------------------------------------------
         * Create & Initialize The Application
         * -------------------------------------------------------------------------
         */
        $app = new App($container, $rootPath);
        $framework->init($app);

        return $app;
    }
}