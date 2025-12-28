<?php

declare(strict_types=1);

namespace Strux\Component\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ResponseStatus
{
    public int $code;

    public function __construct(int $code)
    {
        $this->code = $code;
    }
}