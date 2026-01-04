<?php

declare(strict_types=1);

namespace Strux\Auth\Events;

class UserLoggedOut
{
    public function __construct(
        public ?object $user = null
    )
    {
    }
}