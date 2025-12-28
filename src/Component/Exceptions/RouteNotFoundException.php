<?php

declare(strict_types=1);

namespace Strux\Component\Exceptions;

use Exception;
use Throwable;

/**
 * Class RouteNotFoundException
 *
 * Thrown when no route matches the requested URI.
 */
class RouteNotFoundException extends Exception
{
    public function __construct(string $message = "404 Not Found", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}