<?php

declare(strict_types=1);

namespace Strux\Support\Helpers;

/**
 * Class Utils
 * General purpose utility helper methods.
 */
class Utils
{
    /**
     * Generates a unique random string identifier.
     * *
     * @param int $length Length of the string
     * @param bool $upperChars Include uppercase letters
     * @param bool $lowerChars Include lowercase letters
     * @param bool $digits Include numbers
     * @return string|int
     */
    public static function generateId(
        int  $length = 10,
        bool $upperChars = true,
        bool $lowerChars = true,
        bool $digits = true
    ): string|int
    {
        $characters = '';

        if ($upperChars) {
            $characters .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        }

        if ($lowerChars) {
            $characters .= 'abcdefghijklmnopqrstuvwxyz';
        }

        if ($digits) {
            $characters .= '0123456789';
        }

        if (empty($characters)) {
            return '';
        }

        $randomString = '';
        $maxIndex = strlen($characters) - 1;

        try {
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[random_int(0, $maxIndex)];
            }
        } catch (\Exception $e) {
            // Fallback to rand() if random_int fails (rare)
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $maxIndex)];
            }
        }

        if (!$upperChars && !$lowerChars && $digits) return (int)$randomString;

        return $randomString;
    }

    /**
     * Converts a CamelCase or PascalCase string to snake_case.
     * * @param string $input e.g., "UserActivity"
     * @return string e.g., "user_activity"
     */
    public static function toSnakeCase(string $input): string
    {
        if (empty($input)) {
            return '';
        }
        return strtolower(preg_replace('/(?<=[a-z0-9])([A-Z])/', '_$1', $input));
    }

    /**
     * Pluralizes a simple English word (basic implementation).
     * * @param string $input e.g., "Category"
     * @return string e.g., "categories"
     */
    public static function getPluralName(string $input): string
    {
        if (empty($input)) {
            return '';
        }

        $baseName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));

        if (str_ends_with($baseName, 'y') && !in_array(substr($baseName, -2, 1), ['a', 'e', 'i', 'o', 'u'])) {
            return substr($baseName, 0, -1) . 'ies';
        }

        if (str_ends_with($baseName, 's')) {
            return $baseName . 'es';
        }

        return $baseName . 's';
    }
}