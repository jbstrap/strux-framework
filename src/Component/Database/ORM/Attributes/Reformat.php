<?php

declare(strict_types=1);

namespace Strux\Component\Database\ORM\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Reformat
{
    public function __construct(
        public ?string $get = null,
        public ?string $set = null
    ) {}
}
