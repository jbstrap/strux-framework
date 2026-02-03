<?php

declare(strict_types=1);

namespace Strux\Support\Bridge;

use Strux\Support\FrameworkBridge;

/**
 * @method static \Strux\Component\Routing\Router middleware(array $middleware)
 * @method static \Strux\Component\Routing\Router defaults(array $defaults)
 * @method static \Strux\Component\Routing\Router name(string $name)
 * @method static \Strux\Component\Routing\Router setExtra(array $data)
 * @method static \Strux\Component\Routing\Router cache(int $ttl)
 * @method static \Strux\Component\Routing\Router get(string $uri, mixed $handler)
 * @method static \Strux\Component\Routing\Router post(string $uri, mixed $handler)
 * @method static \Strux\Component\Routing\Router put(string $uri, mixed $handler)
 * @method static \Strux\Component\Routing\Router patch(string $uri, mixed $handler)
 * @method static \Strux\Component\Routing\Router delete(string $uri, mixed $handler)
 * @method static \Strux\Component\Routing\Router options(string $uri, mixed $handler)
 * @method static \Strux\Component\Routing\Router head(string $uri, mixed $handler)
 * @method static \Strux\Component\Routing\Router any(string $uri, mixed $handler)
 * @method static \Strux\Component\Routing\Router addRedirect(string $fromPath, string $toTarget, int $statusCode = 301, ?string $routeNameTarget = null, ?array $targetAction = null)
 * @method static void group(array|string $attributes, callable $callback)
 * @method static string route(string $name, array $parameters = [], string $method = 'GET')
 * @method static array getRoutes()
 * @method static array getRedirectRoutes()
 * @method static array getCurrentRoute()
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