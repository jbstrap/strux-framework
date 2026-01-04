<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Date implements RulesInterface
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'The field must be a valid date.';
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (strtotime((string)$value) === false) {
            return $this->message;
        }

        return null;
    }
}