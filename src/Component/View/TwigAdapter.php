<?php

declare(strict_types=1);

namespace Strux\Component\View;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Strux\Component\View\Context\ContextInterface;
use Strux\Component\View\Twig\StruxExtension;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;

class TwigAdapter implements ViewInterface
{
    private Environment $twig;
    private ContainerInterface $container;
    private array $contextProviders;
    private bool $globalDataLoaded = false;

    /**
     * @throws LoaderError
     */
    public function __construct(ContainerInterface $container, array $viewConfig, array $contextProviders)
    {
        $this->container = $container;
        $this->contextProviders = $contextProviders;

        $paths = $viewConfig['template_paths'] ?? [];
        $loader = new FilesystemLoader($paths['default'] ?? []);

        foreach ($paths as $namespace => $path) {
            if ($namespace !== 'default') {
                $loader->addPath($path, $namespace);
            }
        }

        $twigOptions = $viewConfig['twig'] ?? [];
        $this->twig = new Environment($loader, [
            'cache' => $twigOptions['cache_path'] ?? false,
            'debug' => $twigOptions['debug'] ?? false,
        ]);

        $this->twig->addExtension(new StruxExtension());
    }

    public function render(string $template, array $data = []): string
    {
        // 1. Load Global Context ONCE
        // This ensures shared variables (Auth, Config, etc.) are available
        // to all templates, partials, and macros automatically.
        if (!$this->globalDataLoaded) {
            $this->addGlobalContext();
            $this->globalDataLoaded = true;
        }

        if (empty($template)) {
            throw new InvalidArgumentException('Template name cannot be empty.');
        }

        // Normalize template name to always end with ".html.twig"
        $template = preg_replace('/(\.html\.twig|\.twig|\.html)$/', '', $template);
        $template .= '.html.twig';

        if (str_contains($template, '.php')) {
            throw new InvalidArgumentException('PHP templates are not supported in TwigAdapter.');
        }

        return $this->twig->render($template, $data);
    }

    /**
     * Instantiates and processes all registered context providers.
     */
    private function gatherGlobalData(): array
    {
        $globalData = [];
        foreach ($this->contextProviders as $providerClass) {
            if ($this->container->has($providerClass)) {
                /** @var ContextInterface $providerInstance */
                $providerInstance = $this->container->get($providerClass);
                if (method_exists($providerInstance, 'process')) {
                    $globalData = array_merge($globalData, $providerInstance->process());
                }
            }
        }
        return $globalData;
    }

    /**
     * Adds the global context data to the Twig environment.
     */
    private function addGlobalContext(): void
    {
        $globalData = $this->gatherGlobalData();
        foreach ($globalData as $key => $value) {
            // This is the magic method for Twig globals
            $this->twig->addGlobal($key, $value);
        }
    }
}