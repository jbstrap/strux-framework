<?php

declare(strict_types=1);

namespace Strux\Component\Events\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class Listener
{
    public function __construct(
        public ?string $event = null,
        public ?string $method = null
    )
    {
    }
}