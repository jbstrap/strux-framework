<?php

declare(strict_types=1);

namespace Strux\Component\Console\Traits;

use Exception;
use PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Strux\Component\Config\Config;
use Strux\Component\Console\Output;
use Strux\Component\Queue\WorkerInterface;

trait QueueCommands
{
    abstract protected function getPdo(): PDO;

    abstract protected function initTable(string $table, string $sql, bool $verbose, ?string $checkDir = null, string $componentName = 'Table'): void;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getQueueTable(): string
    {
        try {
            /** @var Config $config */
            $config = $this->container->get(Config::class);
            return $config->get('queue.connections.database.table', '_jobs');
        } catch (Exception $e) {
            return '_jobs';
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function getQueueFailedTable(): string
    {
        try {
            /** @var Config $config */
            $config = $this->container->get(Config::class);
            return $config->get('queue.failed.table', '_failed_jobs');
        } catch (Exception $e) {
            return '_failed_jobs';
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function initQueue(bool $verbose = false): void
    {
        $table = $this->getQueueTable();
        $failedTable = $this->getQueueFailedTable();

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `queue` VARCHAR(255) NOT NULL DEFAULT 'default',
            `payload` LONGTEXT NOT NULL,
            `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `reserved_at` INT UNSIGNED NULL,
            `available_at` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `queue_reserved_at` (`queue`, `reserved_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->initTable($table, $sql, $verbose, null, 'Queue');

        $failedSql = "CREATE TABLE IF NOT EXISTS `$failedTable` (
            `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `connection` VARCHAR(255) NOT NULL,
            `queue` VARCHAR(255) NOT NULL,
            `payload` LONGTEXT NOT NULL,
            `exception` LONGTEXT NOT NULL,
            `failed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->initTable($failedTable, $failedSql, $verbose, null, 'Failed Queue');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function workQueue(): void
    {
        Output::info("Queue worker started. Press Ctrl+C to stop.");
        $tableName = $this->getQueueTable();

        /** @var PDO $pdo */
        $pdo = $this->container->get(PDO::class);

        try {
            $pdo->query("SELECT 1 FROM `$tableName` LIMIT 1");
        } catch (Exception $e) {
            Output::error("Error: Queue table '$tableName' not found. Run 'php bin/console queue:init'.\n");
            exit(1);
        }

        /** @var WorkerInterface $worker */
        $worker = $this->container->get(WorkerInterface::class);

        if (!$worker) {
            Output::error("Error: Queue Worker not found in container.\n");
            exit(1);
        }
        $worker->process('default');
    }
}