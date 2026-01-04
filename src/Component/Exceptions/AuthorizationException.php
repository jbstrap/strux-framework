<?php

namespace Strux\Component\Exceptions;

use Exception;
use Throwable;

class AuthorizationException extends Exception
{
    public function __construct(string $message = "403 Unauthorized", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}