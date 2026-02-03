<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use PDO;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Strux\Component\Cache\Cache;
use Strux\Component\Config\Config;
use Strux\Component\Http\Cookie;
use Strux\Component\Http\CookieInterface;
use Strux\Component\Mail\Mailer;
use Strux\Component\Mail\MailerInterface;
use Strux\Component\Queue\Queue;
use Strux\Component\Session\SessionInterface;
use Strux\Component\Session\SessionManager;
use Strux\Component\View\ViewInterface;
use Strux\Support\Helpers\Flash;
use Strux\Support\Helpers\FlashInterface;

class InfrastructureRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $this->container->singleton(
            SessionInterface::class,
            static fn(ContainerInterface $c) => new SessionManager(
                $c->get(Config::class),
                $c
            )
        );

        $this->container->singleton(
            CookieInterface::class,
            static fn(ContainerInterface $c) => new Cookie($c->get(Config::class))
        );

        $this->container->singleton(
            FlashInterface::class,
            static fn(ContainerInterface $c) => new Flash($c->get(SessionInterface::class))
        );

        $this->container->singleton(
            CacheInterface::class,
            static fn(ContainerInterface $c) => new Cache(
                $c->get(Config::class),
                $c->get(LoggerInterface::class)
            )
        );

        $this->container->transient(
            MailerInterface::class,
            static fn(ContainerInterface $c) => new Mailer(
                $c->get(Config::class),
                $c->get(ViewInterface::class),
                $c->get(LoggerInterface::class)
            )
        );

        $this->container->singleton(
            Queue::class,
            static fn(ContainerInterface $c) => new Queue(
                $c->get(Config::class),
                $c->get(PDO::class)
            )
        );
    }
}