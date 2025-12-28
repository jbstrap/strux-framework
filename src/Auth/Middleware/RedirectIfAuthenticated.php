<?php

declare(strict_types=1);

namespace Strux\Auth\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Strux\Auth\AuthManager;
use Strux\Component\Config\Config;
use Strux\Component\Routing\Router;

readonly class RedirectIfAuthenticated implements MiddlewareInterface
{
    public function __construct(
        private AuthManager              $auth,
        private ResponseFactoryInterface $responseFactory,
        private Router                   $router,
        private Config                   $config
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Check if user is authenticated
        if ($this->auth->sentinel('web')->check()) {

            // 1. Check Query Params (e.g. GET /login?next=/profile)
            $queryParams = $request->getQueryParams();
            $next = $queryParams['next'] ?? null;

            // 2. Check Parsed Body (if logic flows from a form, though rare for this middleware)
            if (empty($next)) {
                $body = $request->getParsedBody();
                $next = is_array($body) ? ($body['next'] ?? null) : null;
            }

            // 3. Fallback to Config Default
            if (empty($next) || $next === '/') {
                $next = $this->config->get('auth.defaults.redirect_to', '/');
            }

            // Generate URL
            try {
                // If it looks like a path (starts with /), use it directly.
                // Otherwise, treat it as a named route.
                if (str_starts_with($next, '/')) {
                    $url = $next;
                } else {
                    $url = $this->router->route($next);
                }
            } catch (\Exception $e) {
                // Fallback if route name not found
                $url = '/';
            }

            return $this->responseFactory->createResponse(302)
                ->withHeader('Location', $url);
        }

        return $handler->handle($request);
    }
}