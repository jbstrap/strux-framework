<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Attributes;

use Attribute;
use Strux\Component\Database\ORM\Enums\DataType;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Transform
{
    public function __construct(
        public DataType $type
    ) {}
}
