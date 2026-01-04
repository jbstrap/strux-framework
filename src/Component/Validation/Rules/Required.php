<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Required implements RulesInterface
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'This field is required';
    }

    public function validate($value, $data = null): ?string
    {
        return empty($value) ? $this->message : null;
    }
}