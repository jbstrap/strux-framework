<?php

declare(strict_types=1);

namespace Strux\Foundation;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Strux\Component\Http\Middleware\Dispatcher as MiddlewareDispatcher;
use Strux\Component\Http\Psr7\ServerRequestCreator;
use Strux\Component\Http\ResponseEmitter;
use Strux\Component\Routing\RouteDispatcher;
use Strux\Component\Routing\Router;

class App
{
    private ContainerInterface $container;
    private array $globalMiddleware = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getRouter(): Router
    {
        return $this->container->get(Router::class);
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @return LoggerInterface
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->container->get(LoggerInterface::class);
    }

    public function addMiddleware($middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * Runs the application, handles the request, and emits the response.
     */
    public function run(): void
    {
        // 1. Create the request from globals.
        $requestCreator = $this->container->get(ServerRequestCreator::class);
        $request = $requestCreator->fromGlobals();

        // 2. Define the final handler, which is our new RouteDispatcher.
        $routeDispatcher = $this->container->get(RouteDispatcher::class);

        // 3. Create our main middleware dispatcher for the global middleware stack.
        $middlewareDispatcher = new MiddlewareDispatcher($this->globalMiddleware, $this->container);

        // 4. Dispatch the request through the global middleware to the route dispatcher.
        // This will produce a PSR-7 ResponseInterface object.
        $response = $middlewareDispatcher->dispatch($request, $routeDispatcher);

        // 5. Use the ResponseEmitter to send the final PSR-7 response to the client.
        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);
    }
}
