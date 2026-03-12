<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Strux\Component\Config\Config;
use Strux\Component\Queue\Queue;
use Strux\Component\Queue\QueueInterface;
use Strux\Component\Queue\Worker;
use Strux\Component\Queue\WorkerInterface;

class QueueRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $this->container->singleton(QueueInterface::class, static function (ContainerInterface $c) {
            return new Queue(
                $c->get(Config::class),
                $c->get(PDO::class)
            );
        });

        $this->container->singleton(WorkerInterface::class, static function (ContainerInterface $c) {
            return new Worker(
                $c->get(Queue::class),
                $c->get(LoggerInterface::class),
                $c
            );
        });
    }
}