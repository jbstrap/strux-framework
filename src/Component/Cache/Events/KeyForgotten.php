<?php

declare(strict_types=1);

namespace Strux\Component\Cache\Events;

class KeyForgotten
{
    public function __construct(
        public string $key,
        public array  $tags = []
    )
    {
    }
}