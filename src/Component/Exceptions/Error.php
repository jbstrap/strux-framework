<?php

declare(strict_types=1);

namespace Strux\Component\Exceptions;

use Exception;
use Throwable;

/**
 * Class Error
 *
 * Base exception class for application-specific errors.
 * This is distinct from PHP's internal \Error throwable.
 * Use this for significant errors in your application logic where \Exception
 * might be too generic or when you want a common base for application faults.
 */
class Error extends Exception // Or \RuntimeException if preferred for src errors
{
    /**
     * Application Error constructor.
     *
     * @param string $message The Exception message to throw.
     * @param int $code The Exception code (application-specific).
     * @param Throwable|null $previous The previous throwable used for exception chaining.
     */
    public function __construct(string $message = "An application error occurred.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get a human-readable name for the error type.
     * Could be overridden in child classes for more specific error types.
     *
     * @return string
     */
    public function getName(): string
    {
        return 'Application Error';
    }

    // You could add more application-specific methods or properties here if needed.
    // For example, methods to get context data related to the error.
    // protected array $context = [];
    // web function getContext(): array { return $this->context; }
}