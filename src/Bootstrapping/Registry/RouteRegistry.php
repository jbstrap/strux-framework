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
        $this->container->singleton(Router::class, function (ContainerInterface $c) {
            return new Router($c->get(ServerRequestInterface::class));
        });

        $this->container->singleton(RouterLoader::class, function (ContainerInterface $c) {
            return new RouterLoader($c->get(Router::class), $c, $c->get(LoggerInterface::class));
        });

        $this->container->singleton(ParameterResolver::class, function (ContainerInterface $c) {
            return new ParameterResolver($c);
        });

        $this->container->singleton(RouteDispatcher::class, function (ContainerInterface $c) {
            return new RouteDispatcher(
                $c,
                $c->get(Router::class),
                $c->get(ParameterResolver::class)
            );
        });
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

        $legacyRoutesPath = ROOT_PATH . '/etc/routes/web.php';
        if (file_exists($legacyRoutesPath)) {
            $webRoutes = require $legacyRoutesPath;
            if (is_callable($webRoutes)) {
                $webRoutes($router);
            }
        }

        $mode = $config->get('app.mode', 'domain');

        if ($mode === 'standard') {
            // --- Standard Mode Structure ---
            $webControllerDir = ROOT_PATH . '/src/Controller';
            $apiControllerDir = ROOT_PATH . '/src/Controller/Api';
        } else {
            // --- Domain Mode Structure (Default) ---
            $webControllerDir = ROOT_PATH . '/src/Http/Controllers/Web';
            $apiControllerDir = ROOT_PATH . '/src/Http/Controllers/Api';
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