<?php

declare(strict_types=1);

namespace Strux\Component\Middleware\ErrorFormatter;

use Throwable;

class PlainFormatter extends AbstractFormatter
{
    /**
     * @var string[]
     */
    protected array $contentTypes = [
        'text/plain',
    ];

    protected function format(Throwable $error): string
    {
        $output = sprintf(
            "[%s] %s\nMessage: %s\nFile: %s\nLine: %s\n",
            get_class($error),
            $error->getCode(),
            $error->getMessage(),
            $error->getFile(),
            $error->getLine()
        );

        if ($this->appDebug) {
            $output .= "\n--- Stack Trace ---\n";
            $output .= $error->getTraceAsString();
        }

        return $output;
    }
}