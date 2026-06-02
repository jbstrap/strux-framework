<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Stash
{
    /**
     * @param int $ttl Time to live in seconds
     */
    public function __construct(public int $ttl)
    {
    }
}
