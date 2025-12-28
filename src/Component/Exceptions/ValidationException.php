<?php

declare(strict_types=1);

namespace Strux\Component\Exceptions;

use Exception;
use Throwable;

class ValidationException extends Exception
{
    public function __construct(
        public readonly array $errors,
        string                $message = "The given data was invalid.",
        int                   $code = 422,
        ?Throwable            $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}