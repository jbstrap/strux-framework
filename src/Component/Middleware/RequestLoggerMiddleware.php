<?php

declare(strict_types=1);

namespace Strux\Component\Middleware;

use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

// For more precise timing

/**
 * Class RequestLoggerMiddleware
 *
 * A simple PSR-15 middleware to log incoming requests.
 */
class RequestLoggerMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    /**
     * RequestLoggerMiddleware constructor.
     *
     * @param LoggerInterface $logger The PSR-3 logger instance.
     */
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler.
     *
     * @param ServerRequestInterface $request The incoming request.
     * @param RequestHandlerInterface $handler The next request handler.
     * @return ResponseInterface The response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logger->log('info', '[RequestLoggerMiddleware] Processing request logger middleware', [
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
        ]);

        $startTime = microtime(true);
        $startDateTime = new DateTimeImmutable();

        // Log basic request information before passing to the next handler
        $this->logger->info(sprintf(
            "Request Start: [%s] %s %s",
            $startDateTime->format('Y-m-d H:i:s.u'), // Added microseconds for better precision
            $request->getMethod(),
            $request->getUri()->getPath() . ($request->getUri()->getQuery() ? '?' . $request->getUri()->getQuery() : '')
        ), [
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri(),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'N/A',
            'user_agent' => $request->getHeaderLine('User-Agent'),
        ]);

        // Delegate to the next request handler to get the response
        $response = $handler->handle($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // Duration in milliseconds

        // Log response information
        $this->logger->info(sprintf(
            "Request End: [%s] %s %s - Status: %d - Duration: %s ms\n\n",
            (new DateTimeImmutable())->format('Y-m-d H:i:s.u'),
            $request->getMethod(),
            $request->getUri()->getPath(),
            $response->getStatusCode(),
            $duration
        ), [
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
        ]);

        return $response;
    }
}