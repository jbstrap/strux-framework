<?php

declare(strict_types=1);

namespace Strux\Component\Exceptions\Container;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 *
 * Thrown when no entry was found in the container for a given identifier.
 */
class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
    // No specific methods needed here, inherits from Exception and implements the marker interface.
}