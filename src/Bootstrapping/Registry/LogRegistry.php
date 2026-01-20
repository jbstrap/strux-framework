<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Strux\Component\Config\Config;
use Strux\Foundation\Application;

class LogRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $this->container->singleton(LoggerInterface::class, function (ContainerInterface $c) {
            /**@var Config $config */
            $config = $c->get(Config::class);
            $logger = new Logger($config->get('app.name', 'app'));
            $env = $config->get('app.env', 'production');
            $logLevel = $config->get('app.debug', false) ? Level::Debug : Level::Info;
            $logFilePath = $config->get('app.log_dir') . '/app.log';
            if (!is_dir(dirname($logFilePath))) {
                mkdir(dirname($logFilePath), 0775, true);
            }
            $handler = new StreamHandler($logFilePath, $logLevel);
            $handler->setFormatter(new LineFormatter(null, null, true, true));
            $logger->pushHandler($handler);
            return $logger;
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function init(Application $app): void
    {
        /**@var Config $config */
        $config = $this->container->get(Config::class);
        $config->set('app.log_dir', $app->getRootPath() . '/var/logs');
    }
}