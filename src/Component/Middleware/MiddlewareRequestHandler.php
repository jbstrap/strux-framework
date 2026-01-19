<?php

namespace Strux\Component\Middleware;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
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

    public function __construct(
        array                   $middlewareQueue,
        RequestHandlerInterface $fallbackHandler,
        ContainerInterface      $container
    )
    {
        $this->middlewareQueue = $middlewareQueue;
        $this->fallbackHandler = $fallbackHandler;
        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (!isset($this->middlewareQueue[$this->middlewareIndex])) {
            return $this->fallbackHandler->handle($request);
        }

        $middlewareItem = $this->middlewareQueue[$this->middlewareIndex];
        $this->middlewareIndex++;

        $middlewareInstance = is_string($middlewareItem)
            ? $this->container->get($middlewareItem)
            : $middlewareItem;

        if (!$middlewareInstance instanceof PsrMiddlewareInterface) {
            throw new \InvalidArgumentException(
                "Middleware " . (is_object($middlewareInstance)
                    ? get_class($middlewareInstance)
                    : gettype($middlewareInstance)) . " must implement Psr\Http\Server\MiddlewareInterface");
        }

        return $middlewareInstance->process($request, $this);
    }
}