<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class MinLength implements RulesInterface
{
    private int $min;
    private ?string $message;

    public function __construct(int $min, ?string $message = null)
    {
        $this->min = $min;
        $this->message = $message ?? 'Field must be at least 2 characters long.';
    }

    public function validate($value, $data = null): ?string
    {
        return strlen($value) < $this->min ? $this->message : null;
    }
}