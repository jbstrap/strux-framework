<?php

declare(strict_types=1);

namespace Strux\Support;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Strux\Component\Exceptions\Container\ContainerException;

abstract class FrameworkBridge
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    abstract protected static function getAccessor(): string;

    /**
     * @throws ContainerException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function __callStatic($method, $args)
    {
        /** @var object $instance */
        $instance = ContainerBridge::get(static::getAccessor());
        return $instance->$method(...$args);
    }
}