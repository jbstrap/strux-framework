<?php

declare(strict_types=1);

namespace Strux\Component\Exceptions;

use Exception;
use Throwable;

/**
 * Class CSRFMismatchException
 */
class CSRFMismatchException extends Exception
{
    public function __construct(
        string     $message = "CSRF token mismatch. Please refresh and try again.",
        int        $code = 419,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }
}