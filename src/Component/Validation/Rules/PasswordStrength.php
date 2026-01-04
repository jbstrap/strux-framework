<?php

declare(strict_types=1);

namespace Strux\Component\Validation\Rules;

class PasswordStrength implements RulesInterface
{
    public function validate($value, $data = null): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $uppercase = '/(?=.*?[A-Z])/';
        $lowercase = '/(?=.*?[a-z])/';
        $digit = '/(?=.*?[0-9])/';
        $symbol = '/(?=.*?[#?!@$%^&*-])/';
        $minLength = 8;

        if (!preg_match($uppercase, $value)) {
            return "Password must contain at least one uppercase letter.";
        }
        if (!preg_match($lowercase, $value)) {
            return "Password must contain at least one lowercase letter.";
        }
        if (!preg_match($digit, $value)) {
            return "Password must contain at least one digit.";
        }
        if (!preg_match($symbol, $value)) {
            return "Password must contain at least one special character.";
        }
        if (strlen($value) < $minLength) {
            return "Password must be at least 8 characters long.";
        }
        return null;
    }
}