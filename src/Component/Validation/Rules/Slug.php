<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

use function preg_match;

class Slug implements RulesInterface
{
    private ?string $message;

    public function __construct(?string $message = null)
    {
        $this->message = $message ?? 'The field must be a valid slug (only lowercase letters, numbers, and hyphens).';
    }

    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null; // Leave empty checks to the Required rule
        }

        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value)) {
            return $this->message;
        }

        return null;
    }
}
