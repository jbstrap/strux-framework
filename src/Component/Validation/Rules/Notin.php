<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Notin implements RulesInterface
{
    private array $disallowedValues;

    public function __construct(string ...$values)
    {
        $this->disallowedValues = $values;
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (in_array((string)$value, $this->disallowedValues)) {
            return 'The selected value is invalid.';
        }

        return null;
    }
}