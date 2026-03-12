<?php

declare(strict_types=1);

namespace Strux\Component\Console;

class Output
{
    // ANSI Background Colors
    private const BG_RED = '41';
    private const BG_GREEN = '42';
    private const BG_YELLOW = '43';
    private const BG_BLUE = '44';

    // ANSI Foreground (Text) Colors
    private const FG_BLACK = '30';
    private const FG_WHITE = '97';
    private const FG_GREEN = '32';
    private const FG_YELLOW = '33';

    /**
     * Prints a blue INFO badge.
     */
    public static function info(string $message): void
    {
        self::writeBadge('INFO', $message, self::BG_BLUE, self::FG_WHITE);
    }

    /**
     * Prints a green SUCCESS badge.
     */
    public static function success(string $message): void
    {
        self::writeBadge('SUCCESS', $message, self::BG_GREEN, self::FG_WHITE);
    }

    /**
     * Prints a yellow WARNING badge.
     */
    public static function warning(string $message): void
    {
        self::writeBadge('WARN', $message, self::BG_YELLOW, self::FG_BLACK);
    }

    /**
     * Prints a red ERROR badge.
     */
    public static function error(string $message): void
    {
        self::writeBadge('ERROR', $message, self::BG_RED, self::FG_WHITE);
    }

    /**
     * Prints a standard line without a badge.
     */
    public static function line(string $message): void
    {
        echo $message . PHP_EOL;
    }

    /**
     * Replicates the interactive choice prompt shown in your screenshot.
     */
    public static function choice(string $question, array $choices, string $default = ''): string
    {
        // Format the question in Green, and the default answer in Yellow
        $defaultLabel = $default !== '' ? " [\033[" . self::FG_YELLOW . "m{$default}\033[0m]" : "";
        echo sprintf("\n\033[%sm%s\033[0m%s:\n", self::FG_GREEN, $question, $defaultLabel);

        // Print options with Yellow keys
        foreach ($choices as $key => $description) {
            $paddedKey = str_pad((string)$key, 8);
            echo sprintf("  [\033[%sm%s\033[0m] %s\n", self::FG_YELLOW, $paddedKey, $description);
        }

        echo "> ";

        // Read input from the terminal
        $handle = fopen("php://stdin", "r");
        $input = trim(fgets($handle));
        fclose($handle);

        return $input !== '' ? $input : $default;
    }

    /**
     * Core method to render the badge with ANSI codes.
     */
    private static function writeBadge(string $label, string $message, string $bg, string $fg): void
    {
        // Add spaces around the label for padding
        $label = " {$label} ";

        // \033[ is the ANSI escape sequence starter.
        // Format: \033[{Background};{Foreground};1m {Text} \033[0m
        // '1m' makes the text bold. '0m' resets the formatting so the rest of the line is normal.
        echo sprintf("\033[%s;%s;1m%s\033[0m %s" . PHP_EOL, $bg, $fg, $label, $message);
    }
}