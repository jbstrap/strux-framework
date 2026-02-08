<?php

declare(strict_types=1);

namespace Strux\Component\Cache\Events;

class CacheMiss
{
    public function __construct(
        public string $key,
        public array  $tags = []
    )
    {
    }
}