<?php

declare(strict_types=1);

namespace Strux\Auth\Events;

class LoginFailed
{
    public function __construct(
        public array $credentials
    )
    {
    }
}