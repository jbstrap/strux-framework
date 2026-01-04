<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Url implements RulesInterface
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'The field must be a valid URL.';
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return $this->message;
        }

        return null;
    }
}