<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use Strux\Component\Config\Config;
use Strux\Component\Events\CallQueuedListener;
use Strux\Component\Events\EventDispatcher;
use Strux\Component\Events\ListenerProvider;
use Strux\Component\Queue\Queue;
use Strux\Component\Queue\ShouldQueue;
use Strux\Foundation\Application;

class EventRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $this->container->singleton(ListenerProviderInterface::class, function (ContainerInterface $c) {
            return new ListenerProvider();
        });

        $this->container->singleton(EventDispatcher::class, function (ContainerInterface $c) {
            return new EventDispatcher($c->get(ListenerProviderInterface::class));
        });

        $this->container->bind(EventDispatcherInterface::class, EventDispatcher::class);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function init(Application $app): void
    {
        $container = $app->getContainer();
        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container->get(EventDispatcher::class);
        $config = $container->get(Config::class);
        $logger = $container->get(LoggerInterface::class);

        $queue = $container->has(Queue::class) ? $container->get(Queue::class) : null;

        $eventsConfig = $config->get('events', []);
        $listenersMap = $eventsConfig['listeners'] ?? [];

        if (!empty($listenersMap)) {
            foreach ($listenersMap as $eventClass => $listeners) {
                foreach ($listeners as $listenerClass) {

                    $callableListener = function (object $event) use ($container, $listenerClass, $queue, $logger) {

                        $listenerInstance = $container->get($listenerClass);

                        if ($listenerInstance instanceof ShouldQueue && $queue) {
                            $job = new CallQueuedListener($listenerClass, $event);
                            $queue->push($job);

                            $logger->info("[EventRegistry] Queued listener {$listenerClass}");

                        } else {
                            if (method_exists($listenerInstance, 'handle')) {
                                $listenerInstance->handle($event);
                            } elseif (is_callable($listenerInstance)) {
                                $listenerInstance($event);
                            }
                        }
                    };

                    $dispatcher->addListener($eventClass, $callableListener);
                }
            }
        }
    }
}