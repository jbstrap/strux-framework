<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Regex implements RulesInterface
{
    private string $pattern;
    private ?string $message;

    public function __construct(string $pattern, ?string $message = null)
    {
        $this->pattern = $pattern;
        $this->message = $message ?? 'The field format is invalid.';
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!preg_match($this->pattern, (string)$value)) {
            return $this->message;
        }

        return null;
    }
}