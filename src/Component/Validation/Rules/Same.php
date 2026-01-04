<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Same implements RulesInterface
{
    private string $otherField;
    private ?string $message;

    public function __construct(string $otherField, ?string $message = null)
    {
        $this->otherField = $otherField;
        $this->message = $message ?? "The field must match {$otherField}.";
    }

    public function validate($value, $data = null): ?string
    {
        if (!isset($data[$this->otherField])) {
            return "The {$this->otherField} field is missing.";
        }

        if ($value !== $data[$this->otherField]) {
            return $this->message;
        }

        return null;
    }
}