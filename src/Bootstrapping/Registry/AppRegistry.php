<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

class AppRegistry extends ServiceRegistry
{
    public function build(): void
    {
        $servicesPath = ROOT_PATH . '/etc/services.php';

        if (!file_exists($servicesPath)) {
            return;
        }

        $services = require $servicesPath;

        if (!is_array($services)) {
            return;
        }

        // 1. Register Singletons (Shared instance for the entire request)
        if (isset($services['singletons']) && is_array($services['singletons'])) {
            foreach ($services['singletons'] as $abstract => $concrete) {
                $this->container->singleton($abstract, $concrete);
            }
        }

        // 2. Register Transients (New instance every time requested)
        if (isset($services['transients']) && is_array($services['transients'])) {
            foreach ($services['transients'] as $abstract => $concrete) {
                if (method_exists($this->container, 'transient')) {
                    $this->container->transient($abstract, $concrete);
                } else {
                    $this->container->bind($abstract, $concrete);
                }
            }
        }

        // 3. Register Scoped
        // Note: In standard PHP-FPM, 'Scoped' is effectively the same as 'Singleton'
        // (shared for the duration of the request).
        if (isset($services['scoped']) && is_array($services['scoped'])) {
            foreach ($services['scoped'] as $abstract => $concrete) {
                if (method_exists($this->container, 'scoped')) {
                    $this->container->scoped($abstract, $concrete);
                } else {
                    // Fallback to singleton if explicit 'scoped' method is missing
                    $this->container->singleton($abstract, $concrete);
                }
            }
        }

        // 4. Handle Legacy/Flat definitions (optional backward compatibility)
        foreach ($services as $abstract => $concrete) {
            if (is_string($abstract) && !in_array($abstract, ['singletons', 'transients', 'scoped'])) {
                $this->container->bind($abstract, $concrete);
            }
        }
    }
}