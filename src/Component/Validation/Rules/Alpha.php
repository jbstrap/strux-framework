<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Alpha implements RulesInterface
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'The field must contain only letters.';
    }

    public function validate($value, $data = null): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        if (!ctype_alpha($value)) {
            return $this->message;
        }

        return null;
    }
}