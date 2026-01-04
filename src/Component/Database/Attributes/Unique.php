<?php

namespace Strux\Component\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Unique
{
    public function __construct(
        public ?string $indexName = null
    )
    {
    }
}