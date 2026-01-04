<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Integer implements RulesInterface
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'The field must be an integer.';
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            return $this->message;
        }

        return null;
    }
}