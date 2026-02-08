<?php

declare(strict_types=1);

namespace Strux\Component\Cache\Events;

use DateInterval;

class KeyWritten
{
    public function __construct(
        public string                $key,
        public mixed                 $value,
        public null|int|DateInterval $ttl = null,
        public array                 $tags = []
    )
    {
    }
}