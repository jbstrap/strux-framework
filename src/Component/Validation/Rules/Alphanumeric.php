<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Alphanumeric implements RulesInterface
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'The field must contain only letters and numbers.';
    }

    public function validate($value, $data = null): ?string
    {
        if (!is_scalar($value) || $value === '') {
            return null;
        }

        if (!ctype_alnum((string)$value)) {
            return $this->message;
        }

        return null;
    }
}