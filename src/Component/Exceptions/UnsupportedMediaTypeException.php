<?php

declare(strict_types=1);

namespace Strux\Component\Exceptions;

use Exception;
use Throwable;

class UnsupportedMediaTypeException extends Exception
{
    public function __construct(
        string     $message = "415 Unsupported Media Type",
        int        $code = 415,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}