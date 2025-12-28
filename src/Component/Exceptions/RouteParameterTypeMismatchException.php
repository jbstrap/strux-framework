<?php

declare(strict_types=1);

namespace Strux\Component\Exceptions;

use InvalidArgumentException;
use Throwable;

/**
 * Class RouteParameterTypeMismatchException
 *
 * Thrown when a route parameter's value does not match its expected type.
 */
class RouteParameterTypeMismatchException extends InvalidArgumentException // Or extend a custom base src exception
{
    private string $parameterName;
    private string $expectedType;
    private mixed $actualValue;

    public function __construct(
        string     $parameterName,
        string     $expectedType,
        mixed      $actualValue,
        string     $message = "",
        int        $code = 0,
        ?Throwable $previous = null
    )
    {
        $this->parameterName = $parameterName;
        $this->expectedType = $expectedType;
        $this->actualValue = $actualValue;

        if (empty($message)) {
            $actualValueString = is_string($actualValue) || is_numeric($actualValue) ? "'$actualValue'" : gettype($actualValue);
            $message = "Route parameter '$parameterName' expects type '$expectedType', but value $actualValueString of type " . gettype($actualValueString) . " was provided.";
        }
        parent::__construct($message, $code, $previous);
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }

    public function getExpectedType(): string
    {
        return $this->expectedType;
    }

    public function getActualValue(): mixed
    {
        return $this->actualValue;
    }
}