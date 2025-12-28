<?php

declare(strict_types=1);

namespace Strux\Component\Exceptions;

use Exception;
use Throwable;

/**
 * Class HttpMethodNotAllowedException
 *
 * Thrown when a route matches the URI but not the HTTP method.
 */
class HttpMethodNotAllowedException extends Exception
{
    private array $allowedMethods;

    public function __construct(
        string     $message = "405 Method Not Allowed",
        array      $allowedMethods = [],
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        $this->allowedMethods = $allowedMethods;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the allowed HTTP methods for the requested URI.
     *
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}