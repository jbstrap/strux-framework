<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class In implements RulesInterface
{
    private array $allowedValues;

    public function __construct(string ...$values)
    {
        $this->allowedValues = $values;
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!in_array((string)$value, $this->allowedValues)) {
            return 'The selected value is invalid.';
        }

        return null;
    }
}