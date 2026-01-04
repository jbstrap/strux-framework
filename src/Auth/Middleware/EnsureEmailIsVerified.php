<?php

declare(strict_types=1);

namespace Strux\Auth\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Strux\Auth\AuthManager;
use Strux\Support\Helpers\FlashServiceInterface;

readonly class EnsureEmailIsVerified implements MiddlewareInterface
{
    public function __construct(
        private AuthManager              $auth,
        private ResponseFactoryInterface $responseFactory,
        private FlashServiceInterface    $flash
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $user = $this->auth->sentinel('web')->user();

        // 1. Check if user is logged in AND has verified email
        if (!$user || (property_exists($user, 'email_verified_at') && $user->email_verified_at === null)) {

            if ($request->getHeaderLine('Accept') === 'application/json') {
                return $this->responseFactory->createResponse(403)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody(\Strux\Component\Http\Psr7\Stream::create(json_encode(['error' => 'Email not verified'])));
            }

            $this->flash->set('error', 'Please verify your email address to access this area.');

            // Redirect to a verification notice page or profile
            return $this->responseFactory->createResponse(302)
                ->withHeader('Location', '/email/verify');
        }

        return $handler->handle($request);
    }
}