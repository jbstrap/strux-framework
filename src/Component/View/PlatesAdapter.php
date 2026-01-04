<?php

declare(strict_types=1);

namespace Strux\Component\View;

use InvalidArgumentException;
use League\Plates\Engine;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class PlatesAdapter implements ViewInterface
{
    private Engine $engine;
    private ContainerInterface $container;
    private array $contextProviders;
    private bool $globalDataLoaded = false;

    public function __construct(ContainerInterface $container, array $viewConfig, array $contextProviders)
    {
        $this->container = $container;
        $this->contextProviders = $contextProviders;

        $paths = $viewConfig['template_paths'] ?? [];
        $this->engine = new Engine($paths['default'] ?? null);

        foreach ($paths as $name => $path) {
            if ($name !== 'default') {
                $this->engine->addFolder($name, $path);
            }
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function render(string $template, array $data = []): string
    {
        if (!$this->globalDataLoaded) {
            $globalData = $this->gatherGlobalData();
            $this->engine->addData($globalData);
            $this->globalDataLoaded = true;
        }

        if (empty($template)) {
            throw new InvalidArgumentException('Template name cannot be empty.');
        }

        return $this->engine->render($template, $data);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function gatherGlobalData(): array
    {
        $globalData = [];
        foreach ($this->contextProviders as $providerClass) {
            if ($this->container->has($providerClass)) {
                $providerInstance = $this->container->get($providerClass);
                if (method_exists($providerInstance, 'process')) {
                    $globalData = array_merge($globalData, $providerInstance->process());
                }
            }
        }
        return $globalData;
    }
}