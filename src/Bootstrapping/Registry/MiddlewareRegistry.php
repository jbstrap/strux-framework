<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use App\Http\Middleware\GuestMiddleware;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Strux\Auth\AuthManager;
use Strux\Auth\Middleware\AuthorizationMiddleware;
use Strux\Component\Config\Config;
use Strux\Component\Middleware\ApiAuthMiddleware;
use Strux\Component\Middleware\ConvertEmptyStringsToNull;
use Strux\Component\Middleware\CsrfProtectionMiddleware;
use Strux\Component\Middleware\ErrorFormatter\HtmlFormatter;
use Strux\Component\Middleware\ErrorFormatter\JsonFormatter;
use Strux\Component\Middleware\ErrorFormatter\PlainFormatter;
use Strux\Component\Middleware\ErrorHandlerMiddleware;
use Strux\Component\Middleware\MaintenanceModeMiddleware;
use Strux\Component\Middleware\MethodOverrideMiddleware;
use Strux\Component\Middleware\PoweredByMiddleware;
use Strux\Component\Middleware\RequestLoggerMiddleware;
use Strux\Component\Routing\Router;
use Strux\Component\Session\SessionInterface;
use Strux\Component\View\ViewInterface;
use Strux\Foundation\App;
use Strux\Support\Helpers\FlashServiceInterface;
use Tuupola\Middleware\CorsMiddleware;

class MiddlewareRegistry extends ServiceRegistry
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function build(): void
    {
        $this->buildErrorHandling();

        $this->container->singleton(RequestLoggerMiddleware::class, function (ContainerInterface $c) {
            return new RequestLoggerMiddleware($c->get(LoggerInterface::class));
        });
        $this->container->singleton(MethodOverrideMiddleware::class, function (ContainerInterface $c) {
            return (new MethodOverrideMiddleware(
                $c->get(ResponseFactoryInterface::class),
                $c->get(LoggerInterface::class)
            ))
                ->getMethods(['HEAD', 'CONNECT', 'TRACE', 'OPTIONS'])
                ->postMethods(['PATCH', 'PUT', 'DELETE', 'COPY', 'LOCK', 'UNLOCK'])
                ->queryParameter('_method')
                ->parsedBodyParameter('_method');
        });
        $this->container->singleton(CsrfProtectionMiddleware::class, function (ContainerInterface $c) {
            return new CsrfProtectionMiddleware(
                $c->get(SessionInterface::class),
                $c->get(LoggerInterface::class),
                $c->get(Config::class)->get('csrf', [])
            );
        });
        $this->container->singleton(AuthorizationMiddleware::class, function (ContainerInterface $c) {
            return new AuthorizationMiddleware(
                $c->get(AuthManager::class),
                $c->get(ResponseFactoryInterface::class),
                $c->get(Router::class),
                $c->get(FlashServiceInterface::class),
                $c->get(Config::class)->get('auth.defaults.redirect_to'),
                $c->get(Config::class)->get('auth.defaults.next_parameter'),
                $c->get(LoggerInterface::class)
            );
        });
        $this->container->singleton(ApiAuthMiddleware::class, function (ContainerInterface $c) {
            return new ApiAuthMiddleware(
                $c->get(AuthManager::class),
                $c->get(ResponseFactoryInterface::class),
                $c->get(LoggerInterface::class)
            );
        });
        $this->container->singleton(GuestMiddleware::class, function (ContainerInterface $c) {
            return new GuestMiddleware(
                $c->get(AuthManager::class),
                $c->get(ResponseFactoryInterface::class),
                $c->get(Router::class),
                $c->get(FlashServiceInterface::class),
                $c->get(Config::class),
                $c->get(LoggerInterface::class)
            );
        });
        $this->container->singleton(MaintenanceModeMiddleware::class, function (ContainerInterface $c) {
            return new MaintenanceModeMiddleware(
                $c->get(ResponseFactoryInterface::class),
                $c->get(LoggerInterface::class),
                $c->get(ViewInterface::class),
                $c->get(Config::class)->get('maintenance', [])
            );
        });

        $this->container->singleton(CorsMiddleware::class, function (ContainerInterface $c) {
            $corsConfig = $c->get(Config::class)->get('cors', []);
            return new CorsMiddleware(
                array_merge($corsConfig, [
                    "logger" => $c->get(LoggerInterface::class),
                    "error" => function (ServerRequestInterface $request, ResponseInterface $response, $args) {
                        $data["status"] = "error";
                        $data["message"] = $args["message"];
                        return $response
                            ->withHeader("Content-Type", "application/json")
                            ->getBody()
                            ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
                    }
                ])
            );
        });

        $this->container->singleton(PoweredByMiddleware::class, function (ContainerInterface $c) {
            $config = $c->get(Config::class)->get('headers.x_powered_by', []);
            return new PoweredByMiddleware($config);
        });

        $this->container->singleton(ConvertEmptyStringsToNull::class, fn() => new ConvertEmptyStringsToNull());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function init(App $app): void
    {
        try {
            $app->addMiddleware($this->container->get(ErrorHandlerMiddleware::class));

            $app->addMiddleware($this->container->get(CorsMiddleware::class));

            $app->addMiddleware($this->container->get(PoweredByMiddleware::class));

            $app->addMiddleware($this->container->get(RequestLoggerMiddleware::class));
            $app->addMiddleware($this->container->get(ConvertEmptyStringsToNull::class));
            $app->addMiddleware($this->container->get(MethodOverrideMiddleware::class));
            $app->addMiddleware($this->container->get(CsrfProtectionMiddleware::class));

            if ($this->container->get(Config::class)->get('maintenance.active', false)) {
                $app->addMiddleware($this->container->get(MaintenanceModeMiddleware::class));
            }
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            throw new $e;
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function buildErrorHandling(): void
    {
        try {
            $appDebug = $this->container->get(Config::class)->get('app.debug', false);
            $this->container->singleton(HtmlFormatter::class, function (ContainerInterface $c) use ($appDebug) {
                return new HtmlFormatter(
                    $c->get(ResponseFactoryInterface::class),
                    $c->get(StreamFactoryInterface::class),
                    $appDebug
                );
            });
            $this->container->singleton(JsonFormatter::class, function (ContainerInterface $c) use ($appDebug) {
                return new JsonFormatter(
                    $c->get(ResponseFactoryInterface::class),
                    $c->get(StreamFactoryInterface::class),
                    $appDebug
                );
            });
            $this->container->singleton(PlainFormatter::class, function (ContainerInterface $c) use ($appDebug) {
                return new PlainFormatter(
                    $c->get(ResponseFactoryInterface::class),
                    $c->get(StreamFactoryInterface::class),
                    $appDebug
                );
            });
            $this->container->singleton(ErrorHandlerMiddleware::class, function (ContainerInterface $c) {
                return new ErrorHandlerMiddleware(
                    [
                        $c->get(HtmlFormatter::class),
                        $c->get(JsonFormatter::class),
                        $c->get(PlainFormatter::class)
                    ],
                    $c->get(HtmlFormatter::class),
                    $c->get(LoggerInterface::class)
                );
            });
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
            throw new $e;
        }
    }
}