<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Strux\Foundation\Application;
use Strux\Support\Bridge\Config;

class LogRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $this->container->singleton(LoggerInterface::class, static function (ContainerInterface $c) {
            $logger = new Logger(Config::get('app.log.name', 'app'));
            $env = Config::get('app.env', 'production');
            if ($env === 'development') {
                $logger->pushHandler(new StreamHandler('php://stderr', Level::Debug));
                $logger->pushHandler(new BrowserConsoleHandler(Level::Debug));
            } else {
                $logger->pushHandler(new RotatingFileHandler(
                    (Config::get('app.log.path') ?? Config::get('app.log_dir')) . '/app.log',
                    7,
                    Level::Warning
                ));
            }
            return $logger;
        });
    }

    public function init(Application $app): void
    {
        Config::set('app.log_dir', $app->getRootPath() . '/var/logs');
    }
}