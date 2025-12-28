<?php

declare(strict_types=1);

namespace Strux\Auth\Middleware;

use App\Domain\Identity\Entity\User;
use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Strux\Auth\Auth;
use Strux\Auth\AuthManager;
use Strux\Component\Attributes\Authorize;
use Strux\Component\Exceptions\AuthorizationException;
use Strux\Component\Routing\Router;
use Strux\Support\Helpers\FlashServiceInterface;

class AuthorizationMiddleware implements MiddlewareInterface
{
    private AuthManager $authManager;
    private ResponseFactoryInterface $responseFactory;
    private Router $router;
    private FlashServiceInterface $flash;
    private ?LoggerInterface $logger;
    private string $loginRouteName;

    public function __construct(
        AuthManager              $authManager,
        ResponseFactoryInterface $responseFactory,
        Router                   $router,
        FlashServiceInterface    $flash,
        string                   $loginRouteName = 'auth.login',
        ?LoggerInterface         $logger = null
    )
    {
        $this->authManager = $authManager;
        $this->responseFactory = $responseFactory;
        $this->router = $router;
        $this->flash = $flash;
        $this->loginRouteName = $loginRouteName;
        $this->logger = $logger;
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     * @throws AuthorizationException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Use the AuthManager to get the 'api' sentinel and check the user
        if ($this->authManager->sentinel('web')->check()) {
            // User is authenticated via token, proceed with the request.
            $userId = $this->authManager->sentinel('web')->id();
            $this->logger?->info("[AuthManagerMiddleware] User with ID {$userId} is authenticated. Proceeding.");
            $routeInfo = $request->getAttribute('route');

            $controller = $routeInfo['controller'] ?? null;
            $method = $routeInfo['method'] ?? null;

            if ($controller && $method) {
                // 1. Check Class Level Attributes
                // Handle case where controller is an object (instantiated) or string (class name)
                $reflectionClass = new ReflectionClass($controller);
                $this->checkAuthorization($reflectionClass);

                // 2. Check Method Level Attributes
                if ($reflectionClass->hasMethod($method)) {
                    $this->checkAuthorization($reflectionClass->getMethod($method));
                }
            }
            return $handler->handle($request);
        }

        $this->logger?->info("[AuthorizationMiddleware] User is not authenticated. Redirecting to login.");

        if ($request->getHeaderLine('Accept') === 'application/json') {
            // If the request expects JSON, return a 401 Unauthorized response
            $this->logger?->info("[AuthorizationMiddleware] Returning 401 Unauthorized for JSON request.");
            $response = $this->responseFactory->createResponse(401);
            $response->getBody()->write(json_encode([
                'error' => [
                    'code' => 401,
                    'type' => 'unauthorized',
                    'message' => 'You must be logged in to access this resource.'
                ]
            ], JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // User is not authenticated, set flash message
        $this->flash->set('error', 'You must be logged in to access this page.');

        // Redirect to the login page
        try {
            // Ensure your router's route() method can generate URLs by name.
            // If not, you might need to construct the URL path directly.
            $loginUrl = $this->router->route(
                $this->loginRouteName,
                ['next' => $request->getUri()->getPath()]
            );
        } catch (InvalidArgumentException $e) {
            $this->logger?->error(
                "[AuthorizationMiddleware] CRITICAL: Login route '$this->loginRouteName' not found or URL generation failed.",
                ['exception_message' => $e->getMessage()]
            );
            // Fallback to a hardcoded path if route generation fails
            $loginUrl = '/login'; // Adjust this fallback as necessary
        }

        $this->logger?->info("[AuthorizationMiddleware] Redirecting to: $loginUrl");

        return $this->responseFactory->createResponse(302)
            ->withHeader('Location', $loginUrl);
    }

    /**
     * @throws AuthorizationException
     */
    private function checkAuthorization(ReflectionClass|ReflectionMethod $reflector): void
    {
        $attributes = $reflector->getAttributes(Authorize::class);

        foreach ($attributes as $attribute) {
            /** @var Authorize $authAttr */
            $authAttr = $attribute->newInstance();

            /** @var User $user */
            $user = Auth::user();

            if (!$user) {
                throw new AuthorizationException("Unauthenticated.", 401);
            }

            // Check Roles
            if (!empty($authAttr->roles)) {
                if (!$user->hasRole($authAttr->roles)) {
                    throw new AuthorizationException("User does not have the required role.", 403);
                }
            }

            // Check Permissions
            if (!empty($authAttr->permissions)) {
                if (!$user->hasPermission($authAttr->permissions)) {
                    throw new AuthorizationException("User does not have the required permission.", 403);
                }
            }
        }
    }
}
