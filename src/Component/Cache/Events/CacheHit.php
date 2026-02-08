<?php

declare(strict_types=1);

namespace Strux\Component\Cache\Events;

class CacheHit
{
    public function __construct(
        public string $key,
        public mixed  $value,
        public array  $tags = []
    )
    {
    }
}