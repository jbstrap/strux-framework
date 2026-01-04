<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class MaxLength implements RulesInterface
{
    private int $max;
    private string $message;

    public function __construct(int $max, string $message)
    {
        $this->max = $max;
        $this->message = $message;
    }

    public function validate($value, $data = null): ?string
    {
        return strlen($value) <= $this->max ? $this->message : null;
    }
}