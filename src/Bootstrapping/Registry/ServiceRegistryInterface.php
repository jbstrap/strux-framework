<?php

declare(strict_types=1);

namespace Strux\Bootstrapping\Registry;

use Strux\Foundation\App;

interface ServiceRegistryInterface
{
    /**
     * Build service bindings for the container.
     */
    public function build(): void;

    /**
     * Initialize the registry's services after the application has been constructed.
     */
    public function init(App $app): void;
}