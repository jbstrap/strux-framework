<?php

declare(strict_types=1);

namespace Strux\Component\Middleware\ErrorFormatter;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface FormatterInterface
{
    /**
     * Check whether this formatter can handle the request type (e.g., based on Accept header).
     */
    public function isValid(ServerRequestInterface $request): bool;

    /**
     * Create a PSR-7 response for the given error.
     */
    public function handle(Throwable $error, ServerRequestInterface $request): ResponseInterface;
}