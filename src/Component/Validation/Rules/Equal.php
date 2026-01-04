<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class Equal implements RulesInterface
{
    private string $otherField;
    private ?string $message;

    public function __construct(string $otherField, ?string $message = null)
    {
        $this->otherField = $otherField;
        $this->message = $message;
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!isset($data[$this->otherField])) {
            return "The {$this->otherField} field is missing.";
        }

        return $value !== $data[$this->otherField] ?
            $this->message ?? "Field do not match {$this->otherField}."
            : null;
    }
}