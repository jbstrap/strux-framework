<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Numeric implements RulesInterface
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'The field must be a number.';
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return $this->message;
        }

        return null;
    }
}