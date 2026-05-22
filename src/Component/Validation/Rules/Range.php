<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Range implements RulesInterface
{
    private float|int $min;
    private float|int $max;
    private ?string $message;

    public function __construct(
        string|int|float $min,
        string|int|float $max,
        ?string $message = null
    ) {
        $this->min = is_numeric($min) ? (float)$min : 0;
        $this->max = is_numeric($max) ? (float)$max : 0;
        $this->message = $message ?? sprintf('The value must be between %s and %s.', $min, $max);
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null; // Leave empty checks to the Required rule
        }

        if (!is_numeric($value)) {
            return 'The value must be a number.';
        }

        $numericValue = (float)$value;

        if ($numericValue < $this->min || $numericValue > $this->max) {
            return $this->message;
        }

        return null;
    }
}
