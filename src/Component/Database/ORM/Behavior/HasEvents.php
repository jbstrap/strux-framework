<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Behavior;

use Psr\EventDispatcher\EventDispatcherInterface;
use Strux\Support\ContainerBridge;
use Throwable;

trait HasEvents
{
    /**
     * @var bool Indicates if all model events are globally disabled.
     */
    protected static bool $allEventsDisabled = false;

    /**
     * @var array<string> List of specific event classes that are globally disabled.
     */
    protected static array $disabledSpecificEvents = [];

    /**
     * Dispatch a model event.
     *
     * @param object $event
     * @return object
     */
    protected function fireModelEvent(object $event): object
    {
        if (static::$allEventsDisabled || in_array(get_class($event), static::$disabledSpecificEvents, true)) {
            return $event;
        }

        try {
            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = ContainerBridge::resolve(EventDispatcherInterface::class);
            return $dispatcher->dispatch($event);
        } catch (Throwable $e) {
            // Event dispatcher not available or error occurred, silently ignore
            return $event;
        }
    }

    /**
     * Start configuring which model events should be disabled.
     *
     * @return EventDisabler
     */
    public static function disableEvents(): EventDisabler
    {
        return new EventDisabler();
    }

    /**
     * Re-enable all globally disabled model events.
     */
    public static function enableAllEvents(): void
    {
        static::$allEventsDisabled = false;
        static::$disabledSpecificEvents = [];
    }

    /**
     * Set the disabled status of all model events.
     * @internal
     */
    public static function _setAllEventsDisabled(bool $disabled): void
    {
        static::$allEventsDisabled = $disabled;
    }

    /**
     * Set specific events to be disabled.
     * @internal
     */
    public static function _setDisabledSpecificEvents(array $events): void
    {
        static::$disabledSpecificEvents = $events;
    }
}
