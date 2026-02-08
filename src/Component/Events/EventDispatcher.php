<?php

declare(strict_types=1);

namespace Strux\Component\Events;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;
use Psr\EventDispatcher\StoppableEventInterface;

class EventDispatcher implements EventDispatcherInterface
{
    private ListenerProviderInterface $listenerProvider;

    public function __construct(ListenerProviderInterface $listenerProvider)
    {
        $this->listenerProvider = $listenerProvider;
    }

    /**
     * Provide all relevant listeners with an event to process.
     *
     * @param object $event The object to process.
     * @return object The event object, possibly modified by listeners.
     */
    public function dispatch(object $event): object
    {
        $isStoppable = $event instanceof StoppableEventInterface;

        $listeners = $this->listenerProvider->getListenersForEvent($event);

        foreach ($listeners as $listener) {
            if ($isStoppable && $event->isPropagationStopped()) {
                break;
            }
            $listener($event);
        }

        return $event;
    }

    /**
     * Method to add a listener.
     */
    public function addListener(string $eventClass, callable $listener): void
    {
        if (method_exists($this->listenerProvider, 'addListener')) {
            $this->listenerProvider->addListener($eventClass, $listener);
        } else {
            throw new \RuntimeException("The underlying ListenerProvider does not support adding listeners dynamically.");
        }
    }
}
