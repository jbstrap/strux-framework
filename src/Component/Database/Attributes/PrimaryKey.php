<?php

declare(strict_types=1);

namespace Strux\Component\Database\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class PrimaryKey
{
    public function __construct()
    {
    }
}