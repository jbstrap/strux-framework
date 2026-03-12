<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use Strux\Component\Config\Config;
use Strux\Component\Events\Attributes\Listener;
use Strux\Component\Events\CallQueuedListener;
use Strux\Component\Events\EventDispatcher;
use Strux\Component\Events\ListenerProvider;
use Strux\Component\Queue\QueueInterface;
use Strux\Component\Queue\ShouldQueue;
use Strux\Foundation\Application;
use Strux\Support\ClassFinder;

class EventRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $this->container->singleton(
            ListenerProviderInterface::class,
            static fn() => new ListenerProvider()
        );

        $this->container->singleton(
            EventDispatcher::class,
            static fn(ContainerInterface $c) => new EventDispatcher(
                listenerProvider: $c->get(ListenerProviderInterface::class)
            )
        );

        $this->container->bind(
            EventDispatcherInterface::class,
            EventDispatcher::class
        );
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

        /** @var LoggerInterface $logger */
        $logger = $container->get(LoggerInterface::class);

        /** @var QueueInterface|null $queue */
        $queue = $container->has(QueueInterface::class) ? $container->get(QueueInterface::class) : null;

        /** @var Config $config */
        $config = $container->get(Config::class);
        $mode = $config->get('app.mode', 'standard');

        // Auto-discover listeners based on the application mode
        $this->discoverListeners($container, $dispatcher, $queue, $logger, $mode);
    }

    /**
     * Scans the appropriate directory and registers listeners based on attributes or type-hints.
     */
    protected function discoverListeners(
        ContainerInterface $container,
        EventDispatcher    $dispatcher,
        ?QueueInterface    $queue,
        LoggerInterface    $logger,
        string             $mode
    ): void
    {
        $rootPath = defined('ROOT_PATH') ? ROOT_PATH : getcwd();

        // Determine the directory to scan based on the app mode
        if ($mode === 'domain') {
            // DDD Structure: src/Domain (listeners are usually nested inside domain folders)
            $listenersDir = $rootPath . '/src/Domain';
        } else {
            // Standard Structure: src/Listener
            $listenersDir = $rootPath . '/src/Listener';
        }

        if (!is_dir($listenersDir)) {
            return;
        }

        // Use ClassFinder to get all classes in that directory
        // We assume they are in the 'App' namespace base
        // Optimization: We could filter by Listener::class attribute here if ClassFinder supported it directly,
        // but for now we scan all and check attributes manually.
        $classes = ClassFinder::findClasses($listenersDir, 'App');

        foreach ($classes as $className) {
            if (!class_exists($className)) {
                continue;
            }

            try {
                $reflection = new ReflectionClass($className);
            } catch (ReflectionException $e) {
                continue;
            }

            if ($reflection->isAbstract()) {
                continue;
            }

            // Check for #[Listener] attribute
            $attributes = $reflection->getAttributes(Listener::class);

            // If attribute exists, use it
            if (!empty($attributes)) {
                /** @var Listener $listenerAttribute */
                $listenerAttribute = $attributes[0]->newInstance();

                $eventClass = $listenerAttribute->event;
                $methodName = $listenerAttribute->method ?? 'handle'; // Default to 'handle' if not specified

                // If event class wasn't provided in attribute, try to infer from the method signature
                if ($eventClass === null) {
                    if ($reflection->hasMethod($methodName)) {
                        $eventClass = $this->getEventClassFromMethod($reflection->getMethod($methodName));
                    }
                }

                if ($eventClass) {
                    $this->registerListener($container, $dispatcher, $queue, $logger, $eventClass, $className, $methodName);
                }
                continue; // Done with this class if attribute was found
            }

            // Fallback: If no attribute, look for a 'handle' method with typed event argument (Old behavior)
            if ($reflection->hasMethod('handle')) {
                $eventClass = $this->getEventClassFromMethod($reflection->getMethod('handle'));
                if ($eventClass) {
                    $this->registerListener($container, $dispatcher, $queue, $logger, $eventClass, $className, 'handle');
                }
            }
        }
    }

    /**
     * Helper to extract Event class from method type hint.
     */
    protected function getEventClassFromMethod(\ReflectionMethod $method): ?string
    {
        $parameters = $method->getParameters();

        // The method must accept exactly one argument (The Event)
        if (count($parameters) !== 1) {
            return null;
        }

        $type = $parameters[0]->getType();

        // Must be a named type (e.g. App\Events\UserRegistered)
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return $type->getName();
    }

    /**
     * Registers a single listener, applying queue logic if needed.
     */
    protected function registerListener(
        ContainerInterface $container,
        EventDispatcher    $dispatcher,
        ?QueueInterface    $queue,
        LoggerInterface    $logger,
        string             $eventClass,
        string             $listenerClass,
        string             $methodName = 'handle'
    ): void
    {
        // Create a closure that lazily resolves the listener
        $callableListener = function (object $event) use ($container, $listenerClass, $methodName, $queue, $logger) {
            $listenerInstance = $container->get($listenerClass);

            // Check for ShouldQueue interface
            if ($listenerInstance instanceof ShouldQueue && $queue) {
                // For queued listeners, we typically just queue the class and method execution
                $job = new CallQueuedListener($listenerClass, $event, $methodName);
                $queue->push($job);
                $logger->info("[EventRegistry] Queued listener {$listenerClass}::{$methodName}");
            } else {
                // Execute directly
                if (method_exists($listenerInstance, $methodName)) {
                    $listenerInstance->$methodName($event);
                } elseif ($methodName === '__invoke' && is_callable($listenerInstance)) {
                    $listenerInstance($event);
                }
            }
        };

        $dispatcher->addListener($eventClass, $callableListener);
    }
}