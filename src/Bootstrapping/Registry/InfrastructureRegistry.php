<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Strux\Component\Cache\Cache as CacheManager;
use Strux\Component\Config\Config;
use Strux\Component\Http\Cookie;
use Strux\Component\Mail\Mailer;
use Strux\Component\Mail\MailerInterface;
use Strux\Component\Queue\Queue;
use Strux\Component\Session\SessionInterface;
use Strux\Component\Session\SessionManager;
use Strux\Component\View\ViewInterface;
use Strux\Support\Helpers\Flash as FlashService;
use Strux\Support\Helpers\FlashServiceInterface;

class InfrastructureRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $this->container->singleton(SessionInterface::class, function (ContainerInterface $c) {
            return new SessionManager(
                $c->get(Config::class),
                $c
            );
        });

        $this->container->singleton(Cookie::class, function (ContainerInterface $c) {
            return new Cookie($c->get(Config::class));
        });

        $this->container->singleton(FlashServiceInterface::class, fn(ContainerInterface $c) => new FlashService($c->get(SessionInterface::class)));

        $this->container->singleton(CacheInterface::class, fn(ContainerInterface $c) => new CacheManager(
            $c->get(Config::class),
            $c->get(LoggerInterface::class)
        ));

        $this->container->transient(MailerInterface::class, function (ContainerInterface $c) {
            return new Mailer(
                $c->get(Config::class),
                $c->get(ViewInterface::class),
                $c->get(LoggerInterface::class)
            );
        });

        $this->container->singleton(Queue::class, function (ContainerInterface $c) {
            return new Queue(
                $c->get(Config::class),
                $c->get(PDO::class)
            );
        });
    }
}