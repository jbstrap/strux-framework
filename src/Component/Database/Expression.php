<?php

declare(strict_types=1);

namespace Strux\Component\Database;

use Stringable;

class Expression implements Stringable
{
    protected string $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}