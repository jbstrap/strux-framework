<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Strux\Component\Config\Config;
use Strux\Component\Database\Database;

class DatabaseRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $this->container->singleton(Database::class, fn(ContainerInterface $c) => new Database($c->get(Config::class), $c->get(LoggerInterface::class)));
        $this->container->singleton(PDO::class, fn(ContainerInterface $c) => $c->get(Database::class)->getConnection());
    }
}