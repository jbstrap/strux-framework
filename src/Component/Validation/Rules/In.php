<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class In implements RulesInterface
{
    private array $allowedValues;
    private ?string $message;

    public function __construct(array $values, ?string $message = null)
    {
        $this->allowedValues = explode(',', implode(',', $values));
        $this->message = $message ?? 'The selected value is invalid.';
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!in_array((string)$value, $this->allowedValues)) {
            return $this->message;
        }

        return null;
    }
}