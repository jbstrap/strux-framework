<?php

declare(strict_types=1);

namespace Strux\Component\Events;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use Strux\Component\Exceptions\Container\ContainerException;
use Strux\Component\Queue\Job;
use Strux\Support\ContainerBridge;

class CallQueuedListener extends Job
{
    /**
     * @var string The fully qualified class name of the listener
     */
    public string $listenerClass;

    /**
     * @var object The event object to be processed (must be serializable)
     */
    public object $event;

    public function __construct(string $listenerClass, object $event)
    {
        $this->listenerClass = $listenerClass;
        $this->event = $event;
    }

    /**
     * @throws ContainerException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function handle(): void
    {
        // 1. Get the Container
        // Since Jobs are unserialized by the worker, they lose the app context.
        // We use the static ContainerBridge to regain access to the application.
        if (!class_exists(ContainerBridge::class)) {
            throw new RuntimeException("ContainerBridge not found.");
        }

        $container = ContainerBridge::getContainer();

        if (!$container) {
            throw new RuntimeException("Container not set in ContainerBridge. Is the app booted?");
        }

        // 2. Resolve the Listener
        // This gives us a fresh instance with all dependencies (Mailer, Logger) injected.
        if ($container->has($this->listenerClass)) {
            $listener = $container->get($this->listenerClass);
        } elseif (class_exists($this->listenerClass)) {
            $listener = $container->get($this->listenerClass);
        } else {
            throw new RuntimeException("Listener class '{$this->listenerClass}' not found.");
        }

        // 3. Execute the Listener
        if (method_exists($listener, 'handle')) {
            $listener->handle($this->event);
        } elseif (is_callable($listener)) {
            $listener($this->event);
        } else {
            throw new RuntimeException("Listener '{$this->listenerClass}' is not callable.");
        }
    }
}