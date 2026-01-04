<?php

declare(strict_types=1);

namespace Strux\Component\Middleware\ErrorFormatter;

use Strux\Component\Exceptions\ValidationException;
use Throwable;

class JsonFormatter extends AbstractFormatter
{
    protected array $contentTypes = [
        'application/json',
        'application/x-json',
    ];

    protected function format(Throwable $error): string
    {
        $data = [
            'error' => [
                'type' => get_class($error),
                'code' => $this->determineStatusCode($error),
                'message' => $error->getMessage(),
            ],
        ];

        if ($error instanceof ValidationException) {
            $data['error']['errors'] = $error->errors;
        }

        if ($this->appDebug) {
            $data['error']['file'] = $error->getFile();
            $data['error']['line'] = $error->getLine();
            $data['error']['trace'] = explode("\n", $error->getTraceAsString());
        }

        return (string)json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}