<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Email implements RulesInterface
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'Please enter a valid email address.';
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return !filter_var($value, FILTER_VALIDATE_EMAIL) ? $this->message : null;
    }
}