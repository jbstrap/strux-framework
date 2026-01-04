<?php

namespace Strux\Component\Http\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Dispatcher
{
    private array $queue;
    private ContainerInterface $container;

    public function __construct(array $queue, ContainerInterface $container)
    {
        $this->queue = $queue;
        $this->container = $container;
    }

    /**
     * Dispatches the request through the middleware queue to a final handler.
     */
    public function dispatch(ServerRequestInterface $request, RequestHandlerInterface $finalHandler): ResponseInterface
    {
        $requestHandler = new RequestHandler($this->queue, $finalHandler, $this->container);
        return $requestHandler->handle($request);
    }
}