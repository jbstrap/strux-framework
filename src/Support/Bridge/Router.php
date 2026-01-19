<?php

declare(strict_types=1);

namespace Strux\Support\Bridge;

use Strux\Component\Attributes\Route;
use Strux\Support\FrameworkBridge;

/**
 * @method static Route get(string $uri, mixed $handler)
 * @method static Route post(string $uri, mixed $handler)
 * @method static Route put(string $uri, mixed $handler)
 * @method static Route patch(string $uri, mixed $handler)
 * @method static Route delete(string $uri, mixed $handler)
 * @method static Route options(string $uri, mixed $handler)
 * @method static Route head(string $uri, mixed $handler)
 * @method static Route any(string $uri, mixed $handler)
 * @method static mixed group(array|string $attributes, callable $callback)
 * * @see \Strux\Component\Routing\Router
 */
class Router extends FrameworkBridge
{
    /**
     * @inheritDoc
     */
    protected static function getAccessor(): string
    {
        return \Strux\Component\Routing\Router::class;
    }
}