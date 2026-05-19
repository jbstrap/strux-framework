<?php

declare(strict_types=1);

namespace Strux\Component\ORM\Behavior;

use Strux\Component\ORM\Model;

class EventDisabler
{
    /**
     * Disable all model events globally.
     */
    public function all(): void
    {
        Model::_setAllEventsDisabled(true);
    }

    /**
     * Disable specific model events globally.
     *
     * @param array<string> $events Array of event class names to disable.
     */
    public function only(array $events): void
    {
        Model::_setDisabledSpecificEvents($events);
    }
}
