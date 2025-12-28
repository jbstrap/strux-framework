<?php

declare(strict_types=1);

namespace Strux\Component\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Strux\Auth\AuthManager;

class ApiAuthMiddleware implements MiddlewareInterface
{
    private AuthManager $authManager;
    private ResponseFactoryInterface $responseFactory;
    private ?LoggerInterface $logger;

    public function __construct(
        AuthManager              $authManager,
        ResponseFactoryInterface $responseFactory,
        ?LoggerInterface         $logger = null
    )
    {
        $this->authManager = $authManager;
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Use the AuthManager to get the 'api' sentinel and check the user
        if ($this->authManager->sentinel('api')->check()) {
            // User is authenticated via token, proceed with the request.
            $userId = $this->authManager->sentinel('web')->id();
            $this->logger?->info("[APIAuthManagerMiddleware] User with ID {$userId} is authenticated. Proceeding.");
            return $handler->handle($request);
        }

        // If not authenticated, return a 401 Unauthorized response.
        $response = $this->responseFactory->createResponse(401);
        $response->getBody()->write(json_encode([
            'success' => false,
            'message' => 'Unauthorized',
            'errors' => ['Authentication failed. A valid API token is required.'],
        ]));

        return $response->withHeader('Content-Type', 'application/json');
    }
}