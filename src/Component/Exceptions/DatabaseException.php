<?php

declare(strict_types=1);

namespace Strux\Component\Exceptions;

use Exception;
use PDOException;
use Throwable;

/**
 * Class DatabaseException
 *
 * Custom exception for database-related errors.
 * It can optionally wrap a PDOException or other driver-specific exceptions.
 */
class DatabaseException extends Exception
{
    /**
     * DatabaseException constructor.
     *
     * @param string $message The Exception message to throw.
     * @param int $code The Exception code.
     * @param Throwable|null $previous The previous throwable used for the exception chaining.
     * This is often a PDOException.
     */
    public function __construct(string $message = "A database error occurred.", int $code = 0, ?Throwable $previous = null)
    {
        // If a PDOException is passed as previous, we can use its code and message if desired.
        // For now, we'll allow a custom message but ensure the previous exception is chained.
        if ($previous instanceof PDOException && $message === "A database error occurred.") {
            // You might want to use a more specific message from PDOException
            // or log $previous->errorInfo more directly here or in a handler.
            // For example: $message = "SQLSTATE[{$previous->getCode()}]: " . ($previous->errorInfo[2] ?? $previous->getMessage());
            // However, exposing detailed SQL errors to the client is often a security risk.
            // The generic message is safer by default for user-facing errors.
        }

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the SQLSTATE error code if the previous exception was a PDOException.
     *
     * @return string|null The SQLSTATE error code or null if not available.
     */
    public function getSqlState(): ?string
    {
        $previous = $this->getPrevious();
        if ($previous instanceof PDOException) {
            return $previous->errorInfo[0] ?? null; // SQLSTATE error code
        }
        return null;
    }

    /**
     * Get the driver-specific error code if the previous exception was a PDOException.
     *
     * @return int|null The driver-specific error code or null if not available.
     */
    public function getDriverCode(): ?int
    {
        $previous = $this->getPrevious();
        if ($previous instanceof PDOException && isset($previous->errorInfo[1])) {
            return (int)$previous->errorInfo[1]; // Driver-specific error code
        }
        return null;
    }

    /**
     * Get the driver-specific error message if the previous exception was a PDOException.
     *
     * @return string|null The driver-specific error message or null if not available.
     */
    public function getDriverMessage(): ?string
    {
        $previous = $this->getPrevious();
        if ($previous instanceof PDOException && isset($previous->errorInfo[2])) {
            return $previous->errorInfo[2]; // Driver-specific error message
        }
        return null;
    }
}