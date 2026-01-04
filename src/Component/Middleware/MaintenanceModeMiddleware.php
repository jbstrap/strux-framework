<?php

declare(strict_types=1);

namespace Strux\Component\Middleware;

use Exception;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Strux\Component\View\ViewInterface;

// Optional: For rendering a nice maintenance page via template

/**
 * Class MaintenanceModeMiddleware
 *
 * PSR-15 middleware to handle maintenance mode.
 * If maintenance mode is active, it returns a 503 Service Unavailable response,
 * allowing specified IP addresses or routes to bypass.
 */
class MaintenanceModeMiddleware implements MiddlewareInterface
{
    private ResponseFactoryInterface $responseFactory;
    private ?LoggerInterface $logger;
    private ?ViewInterface $view; // Optional view engine

    /**
     * Flag indicating if maintenance mode is active.
     * @var bool
     */
    private bool $isMaintenanceModeActive = false;

    /**
     * Array of IP addresses allowed bypassing maintenance mode.
     * @var array
     */
    private array $allowedIPs = [];

    /**
     * Array of route paths (regex patterns) allowed bypassing maintenance mode.
     * @var array
     */
    private array $allowedRoutes = [];

    /**
     * Path to the maintenance mode view template (if using a view engine).
     * @var string|null
     */
    private ?string $maintenanceViewTemplate = null;

    /**
     * HTTP status code to return during maintenance.
     * @var int
     */
    private int $statusCode = 503;

    /**
     * Retry-After header value (in seconds or HTTP-date).
     * @var string|int|null
     */
    private mixed $retryAfter = null; // e.g., 3600 (for 1 hour) or a specific date string

    /**
     * MaintenanceModeMiddleware constructor.
     *
     * @param ResponseFactoryInterface $responseFactory
     * @param LoggerInterface|null $logger
     * @param ViewInterface|null $view Optional view engine for rendering maintenance page.
     * @param array $config Configuration options:
     * 'active' (bool): Whether maintenance mode is on.
     * 'allowed_ips' (array): IPs that can bypass maintenance.
     * 'allowed_routes' (array): Route patterns (regex) that can bypass.
     * 'view_template' (string): Template path for the maintenance page.
     * 'status_code' (int): HTTP status code (default 503).
     * 'retry_after' (string|int): Value for Retry-After header.
     */
    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ?LoggerInterface         $logger = null,
        ?ViewInterface           $view = null, // Inject ViewInterface
        array                    $config = []
    )
    {
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
        $this->view = $view;

        $this->isMaintenanceModeActive = (bool)($config['active'] ?? false);
        $this->allowedIPs = (array)($config['allowed_ips'] ?? []);
        $this->allowedRoutes = (array)($config['allowed_routes'] ?? []);
        $this->maintenanceViewTemplate = isset($config['view_template']) ? (string)$config['view_template'] : null;
        $this->statusCode = isset($config['status_code']) ? (int)$config['status_code'] : 503;
        $this->retryAfter = $config['retry_after'] ?? null;

        if ($this->isMaintenanceModeActive) {
            $this->logInfo("Maintenance mode is currently ACTIVE.");
            if (!empty($this->allowedIPs)) {
                $this->logInfo("Allowed IPs: " . implode(', ', $this->allowedIPs));
            }
            if (!empty($this->allowedRoutes)) {
                $this->logInfo("Allowed Routes (patterns): " . implode(', ', $this->allowedRoutes));
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isMaintenanceModeActive) {
            // Maintenance mode is not active, proceed normally
            return $handler->handle($request);
        }

        // Check if the current client IP is allowed
        $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? null;
        if ($clientIp && in_array($clientIp, $this->allowedIPs, true)) {
            $this->logInfo("Bypassing maintenance mode for allowed IP: $clientIp");
            return $handler->handle($request);
        }

        // Check if the current route path is allowed
        $requestPath = $request->getUri()->getPath();
        foreach ($this->allowedRoutes as $allowedRoutePattern) {
            if (preg_match("#^$allowedRoutePattern$#", $requestPath)) {
                $this->logInfo("Bypassing maintenance mode for allowed route: $requestPath (matches pattern: {$allowedRoutePattern})");
                return $handler->handle($request);
            }
        }

        // If not bypassed, return the maintenance response
        $this->logInfo("Maintenance mode enforced for IP: {$clientIp}, Path: {$requestPath}");

        $response = $this->responseFactory->createResponse($this->statusCode);

        if ($this->retryAfter !== null) {
            $response = $response->withHeader('Retry-After', (string)$this->retryAfter);
        }

        // Prevent caching of the maintenance page
        $response = $response
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');


        // Try to render a view template if configured and available
        if ($this->view && $this->maintenanceViewTemplate) {
            try {
                $content = $this->view->render($this->maintenanceViewTemplate, [
                    'title' => 'Service Unavailable',
                    'message' => 'Our site is currently down for scheduled maintenance. We should be back online shortly. Thank you for your patience.',
                    'retry_after' => $this->retryAfter,
                ]);
                $response->getBody()->write($content);
                return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
            } catch (Exception $e) {
                $this->logError("Failed to render maintenance view '$this->maintenanceViewTemplate': " . $e->getMessage(), ['exception' => $e]);
                // Fallback to simple HTML if view rendering fails
            }
        }

        // Fallback simple HTML response
        $response->getBody()->write(
            "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>Service Unavailable</title>" .
            "<style>body{font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; background-color: #f0f0f0; text-align: center; color: #333;} .container{padding: 20px; background-color: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);}</style></head>" .
            "<body><div class='container'><h1>Service Temporarily Unavailable</h1>" .
            "<p>Our site is currently down for scheduled maintenance. We expect to be back online shortly.</p>" .
            "<p>Thank you for your patience.</p>" .
            ($this->retryAfter ? "<p><small>Please try again after: " . (is_numeric($this->retryAfter) ? gmdate("H:i:s \U\T\C", time() + $this->retryAfter) : $this->retryAfter) . "</small></p>" : "") .
            "</div></body></html>"
        );
        return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
    }

    private function logInfo(string $message): void
    {
        $this->logger?->info("[MaintenanceMode] " . $message);
    }

    private function logError(string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->error("[MaintenanceMode] " . $message, $context);
        } else {
            error_log("[MaintenanceMode Error] " . $message . (!empty($context) ? " Context: " . json_encode($context) : ""));
        }
    }
}