<?php

namespace Strux\Component\Middleware;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface as PsrMiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * A private, internal request handler for processing a middleware queue.
 */
class MiddlewareRequestHandler implements RequestHandlerInterface
{
    private array $middlewareQueue;
    private int $middlewareIndex = 0;
    private RequestHandlerInterface $fallbackHandler;
    private ContainerInterface $container;

    public function __construct(array $middlewareQueue, RequestHandlerInterface $fallbackHandler, ContainerInterface $container)
    {
        $this->middlewareQueue = $middlewareQueue;
        $this->fallbackHandler = $fallbackHandler;
        $this->container = $container;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // If we have processed all middleware in the queue, delegate to the final fallback handler.
        if (!isset($this->middlewareQueue[$this->middlewareIndex])) {
            return $this->fallbackHandler->handle($request);
        }

        $middlewareItem = $this->middlewareQueue[$this->middlewareIndex];
        $this->middlewareIndex++; // Move a pointer to the next middleware for the subsequent call

        $middlewareInstance = is_string($middlewareItem)
            ? $this->container->get($middlewareItem)
            : $middlewareItem;

        if (!$middlewareInstance instanceof PsrMiddlewareInterface) {
            throw new \InvalidArgumentException("Middleware " . (is_object($middlewareInstance) ? get_class($middlewareInstance) : gettype($middlewareInstance)) . " must implement Psr\Http\Server\MiddlewareInterface");
        }

        // Process the current middleware, passing this handler itself as the "next" handler.
        // When the middleware calls $handler->handle(), it will re-invoke this method,
        // which will then process the *next* item in the queue because we incremented the index.
        return $middlewareInstance->process($request, $this);
    }
}