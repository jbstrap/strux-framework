<?php

namespace Strux\Component\Database\Seeder;

use PDO;
use RuntimeException;
use Strux\Component\Exceptions\Container\ContainerException;
use Strux\Component\Exceptions\Container\NotFoundException;
use Strux\Foundation\Container;

readonly class SeederRunner
{
    public function __construct(
        private ?PDO      $db,
        private Container $container
    )
    {
    }

    /**
     * @throws ContainerException
     * @throws NotFoundException
     */
    public function run(string $seederClass): void
    {
        if (!class_exists($seederClass)) {
            throw new RuntimeException("Seeder class {$seederClass} not found.");
        }

        $seeder = $this->container->resolve($seederClass);

        if (!$seeder instanceof SeederInterface) {
            throw new RuntimeException("Class {$seederClass} must implement SeederInterface.");
        }

        $seeder->run($this->db);
    }
}