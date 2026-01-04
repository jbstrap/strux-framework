<?php

declare(strict_types=1);

namespace Strux\Component\Http;

class ApiResponse extends Response
{
    public function __construct(
        int    $status = 200,
        mixed  $data = null,
        string $message = '',
        ?array $errors = null
    )
    {
        parent::__construct('', $status);

        $payload = [
            'success' => $status >= 200 && $status < 300,
            'message' => $message,
        ];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        $this->json($payload, $status);
    }
}