<?php

declare(strict_types=1);

namespace Strux\Component\Exceptions\Container;

use Psr\Container\ContainerExceptionInterface;

/**
 * Class ContainerException
 *
 * Base interface representing a generic error in a container.
 */
class ContainerException extends \Exception implements ContainerExceptionInterface
{
    // No specific methods needed here, inherits from Exception and implements the marker interface.
}