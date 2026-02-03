<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Strux\Component\Http\Psr7\Factory\HttpFactory;
use Strux\Component\Http\Psr7\ServerRequestCreator;

class HttpRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $this->container->singleton(HttpFactory::class, static fn() => new HttpFactory());

        $this->container->singleton(
            ResponseFactoryInterface::class,
            static fn(ContainerInterface $c) => $c->get(HttpFactory::class)
        );
        $this->container->singleton(
            StreamFactoryInterface::class,
            static fn(ContainerInterface $c) => $c->get(HttpFactory::class)
        );
        $this->container->singleton(
            ServerRequestFactoryInterface::class,
            static fn(ContainerInterface $c) => $c->get(HttpFactory::class)
        );
        $this->container->singleton(
            UriFactoryInterface::class,
            static fn(ContainerInterface $c) => $c->get(HttpFactory::class)
        );
        $this->container->singleton(
            UploadedFileFactoryInterface::class,
            static fn(ContainerInterface $c) => $c->get(HttpFactory::class)
        );

        $this->container->singleton(
            ServerRequestCreator::class,
            static fn(ContainerInterface $c) => new ServerRequestCreator(
                $c->get(ServerRequestFactoryInterface::class),
                $c->get(UriFactoryInterface::class),
                $c->get(UploadedFileFactoryInterface::class),
                $c->get(StreamFactoryInterface::class)
            )
        );

        $this->container->singleton(ServerRequestInterface::class, static function (ContainerInterface $c) {
            /** @var ServerRequestCreator $creator */
            $creator = $c->get(ServerRequestCreator::class);
            return $creator->fromGlobals();
        });
    }
}
