<?php

declare(strict_types=1);

namespace Strux\Component\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

/** Middleware for overriding HTTP methods using the _method parameter.
 *
 * This middleware allows HTML forms to simulate PUT, PATCH, and DELETE HTTP methods
 * by sending a POST request with a _method parameter containing the desired method.
 * This is useful because HTML forms only support GET and POST methods.
 */
class MethodOverrideMiddleware implements MiddlewareInterface
{
    public const HEADER = 'X-Http-Method-Override';

    private const DEFAULT_QUERY_PARAMETER = '_method';
    private const DEFAULT_BODY_PARAMETER = '_method';

    private const GET_METHOD = 'GET';
    private const POST_METHOD = 'POST';

    private ?ResponseFactoryInterface $responseFactory;

    private ?LoggerInterface $logger;

    /**
     * @var array<string> Allowed methods overriden in GET
     */
    private array $getMethods = ['HEAD', 'CONNECT', 'TRACE', 'OPTIONS'];

    /**
     * @var array<string> Allowed methods overriden in POST
     */
    private array $postMethods = ['PATCH', 'PUT', 'DELETE', 'COPY', 'LOCK', 'UNLOCK'];

    /**
     * @var null|string The POST parameter name
     */
    private ?string $parsedBodyParameter = null;

    /**
     * @var null|string The GET parameter name
     */
    private $queryParameter;

    public function __construct(
        ?ResponseFactoryInterface $responseFactory = null,
        ?LoggerInterface          $logger = null
    )
    {
        $this->responseFactory = $responseFactory;
        $this->logger = $logger;
    }

    /**
     * Set allowed method for GET.
     *
     * @param array<string> $getMethods
     */
    public function getMethods(array $getMethods): self
    {
        $this->getMethods = $getMethods;
        return $this;
    }

    /**
     * Set allowed method for POST.
     *
     * @param array<string> $postMethods
     */
    public function postMethods(array $postMethods): self
    {
        $this->postMethods = $postMethods;
        return $this;
    }

    /**
     * Configure the parameter using in GET requests.
     */
    public function queryParameter(string $name): self
    {
        $this->queryParameter = $name ?: self::DEFAULT_QUERY_PARAMETER;
        return $this;
    }

    /**
     * Configure the parameter using in POST requests.
     */
    public function parsedBodyParameter(string $name): self
    {
        $this->parsedBodyParameter = $name ?: self::DEFAULT_BODY_PARAMETER;
        return $this;
    }

    /**
     * Process a server request and return a response.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->logger->log('info', '[MethodOverrideMiddleware] Processing Method Override middleware', [
            'method' => $request->getMethod(),
            'uri' => (string)$request->getUri()
        ]);

        $method = $this->getOverrideMethod($request);

        if (!empty($method) && $method !== $request->getMethod()) {
            $allowed = $this->getAllowedOverrideMethods($request);

            if (!empty($allowed)) {
                if (in_array($method, $allowed)) {
                    $request = $request->withMethod($method);
                } else {
                    return $this->responseFactory->createResponse(405);
                }
            }
        }

        return $handler->handle($request);
    }

    /**
     * Returns the override method.
     */
    private function getOverrideMethod(ServerRequestInterface $request): string
    {
        if ($request->getMethod() === 'POST' && $this->parsedBodyParameter !== null) {
            $params = $request->getParsedBody();

            // @phpstan-ignore-next-line
            if (isset($params[$this->parsedBodyParameter])) {
                return strtoupper($params[$this->parsedBodyParameter]);
            }
        } elseif ($request->getMethod() === 'GET' && $this->queryParameter !== null) {
            $params = $request->getQueryParams();

            if (isset($params[$this->queryParameter])) {
                return strtoupper($params[$this->queryParameter]);
            }
        }

        return strtoupper($request->getHeaderLine(self::HEADER));
    }

    /**
     * Returns the allowed override methods.
     *
     * @return array<string>
     */
    private function getAllowedOverrideMethods(ServerRequestInterface $request): array
    {
        return match ($request->getMethod()) {
            self::GET_METHOD => $this->getMethods,
            self::POST_METHOD => $this->postMethods,
            default => []
        };
    }
}
