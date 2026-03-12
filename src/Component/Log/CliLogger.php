<?php

declare(strict_types=1);

namespace Strux\Component\Log;

use Psr\Log\AbstractLogger;
use Stringable;
use Strux\Component\Console\Output;

class CliLogger extends AbstractLogger
{
    /**
     * Maps PSR-3 log levels to our visual CLI Badges.
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $formattedMessage = $this->interpolate((string)$message, $context);

        match ($level) {
            'info', 'notice' => Output::info($formattedMessage),
            'warning' => Output::warning($formattedMessage),
            'error', 'critical', 'alert', 'emergency' => Output::error($formattedMessage),
            'debug' => Output::line("DEBUG: " . $formattedMessage),
            default => Output::line($formattedMessage),
        };
    }

    /**
     * Replaces placeholders in the message with context values (Standard PSR-3 behavior).
     */
    private function interpolate(string $message, array $context = []): string
    {
        $replace = [];
        foreach ($context as $key => $val) {
            if (is_scalar($val) || (is_object($val) && method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            }
        }
        return strtr($message, $replace);
    }
}