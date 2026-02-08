<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Strux\Component\Config\Config;
use Strux\Component\Routing\ParameterResolver;
use Strux\Component\Routing\RouteDispatcher;
use Strux\Component\Routing\Router;
use Strux\Component\Routing\RouterLoader;
use Strux\Foundation\Application;

class RouteRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $this->container->singleton(
            Router::class,
            static fn(ContainerInterface $c) => new Router(
                currentRequest: $c->get(ServerRequestInterface::class)
            )
        );

        $this->container->singleton(
            RouterLoader::class,
            static fn(ContainerInterface $c) => new RouterLoader(
                router: $c->get(Router::class),
                container: $c,
                logger: $c->get(LoggerInterface::class)
            )
        );

        $this->container->singleton(
            ParameterResolver::class,
            static fn(ContainerInterface $c) => new ParameterResolver(
                container: $c
            )
        );

        $this->container->singleton(
            RouteDispatcher::class,
            static fn(ContainerInterface $c) => new RouteDispatcher(
                container: $c,
                router: $c->get(Router::class),
                parameterResolver: $c->get(ParameterResolver::class)
            )
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     */
    public function init(Application $app): void
    {
        $router = $app->getRouter();
        /** @var RouterLoader $routerLoader */
        $routerLoader = $this->container->get(RouterLoader::class);
        $config = $this->container->get(Config::class);

        $legacyRoutesPath = $app->getRootPath() . '/etc/routes/web.php';
        if (file_exists($legacyRoutesPath)) {
            require $legacyRoutesPath;
            /*if (is_callable($webRoutes)) {
                $webRoutes($router);
            }*/
        }

        $mode = $config->get('app.mode');

        if ($mode === 'standard') {
            // --- Standard Mode Structure ---
            $webControllerDir = $app->getRootPath() . '/src/Controller';
            $apiControllerDir = $app->getRootPath() . '/src/Controller/Api';
        } else {
            // --- Domain Mode Structure (Default) ---
            $webControllerDir = $app->getRootPath() . '/src/Http/Controllers/Web';
            $apiControllerDir = $app->getRootPath() . '/src/Http/Controllers/Api';
        }

        // 2. Auto-Discover Attribute-Based Web Controllers
        if (is_dir($webControllerDir)) {
            // Scans for #[Route] attributes
            $routerLoader->loadFromDirectory($webControllerDir, isApi: false);
        }

        // 3. Auto-Discover Attribute-Based API Controllers
        if (is_dir($apiControllerDir)) {
            // Scans for #[ApiRoute] attributes
            $routerLoader->loadFromDirectory($apiControllerDir, isApi: true);
        }
    }
}