<?php

declare(strict_types=1);

namespace Strux\Component\Console\Traits;

use Exception;
use PDO;
use ReflectionMethod;
use Strux\Component\Config\Config;
use Throwable;

trait QueueCommands
{
    abstract protected function getPdo(): PDO;

    abstract protected function initTable(string $table, string $sql, bool $verbose, ?string $checkDir = null, string $componentName = 'Table'): void;

    private function getQueueTable(): string
    {
        try {
            $config = $this->container->get(Config::class);
            return $config->get('queue.connections.database.table') ?? '_jobs';
        } catch (Exception $e) {
            return '_jobs';
        }
    }

    private function initQueue(bool $verbose = false): void
    {
        $table = $this->getQueueTable();

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
    }

    private function workQueue(): void
    {
        echo "Queue worker started. Press Ctrl+C to stop.\n";
        $tableName = $this->getQueueTable();

        $pdo = $this->container->get(PDO::class);
        try {
            $pdo->query("SELECT 1 FROM `$tableName` LIMIT 1");
        } catch (Exception $e) {
            echo "Error: Queue table '$tableName' not found. Run 'php console queue:init'.\n";
            exit(1);
        }

        while (true) {
            // Re-fetch PDO to handle potential timeouts or disconnects in long-running process
            // Though container usually returns singleton, PDO handles keepalive.
            // Just using the container instance is standard.
            $job = null;
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("SELECT * FROM {$tableName} WHERE queue = 'default' AND reserved_at IS NULL AND available_at <= ? ORDER BY id ASC LIMIT 1 FOR UPDATE");
                $stmt->execute([time()]);
                $job = $stmt->fetch(PDO::FETCH_OBJ);

                if ($job) {
                    $updateStmt = $pdo->prepare("UPDATE {$tableName} SET reserved_at = ?, attempts = attempts + 1 WHERE id = ?");
                    $updateStmt->execute([time(), $job->id]);
                    $pdo->commit();

                    echo "Processing job: {$job->id}\n";
                    $payload = json_decode($job->payload, true);

                    $jobInstance = unserialize($payload['data']['command']);

                    $dependencies = $this->resolveJobDependencies($jobInstance);

                    if (method_exists($jobInstance, 'handle')) {
                        $jobInstance->handle(...$dependencies);
                    }

                    $deleteStmt = $pdo->prepare("DELETE FROM {$tableName} WHERE id = ?");
                    $deleteStmt->execute([$job->id]);
                    echo "Job {$job->id} processed successfully.\n";
                } else {
                    $pdo->commit();
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo "Error: " . $e->getMessage() . "\n";
                sleep(5);
            }
            if (!$job) sleep(3);
        }
    }

    private function resolveJobDependencies(object $jobInstance): array
    {
        if (!method_exists($jobInstance, 'handle')) {
            return [];
        }
        $dependencies = [];
        $reflectionMethod = new ReflectionMethod($jobInstance, 'handle');
        foreach ($reflectionMethod->getParameters() as $param) {
            $type = $param->getType();
            if ($type && !$type->isBuiltin()) {
                $dependencies[] = $this->container->get($type->getName());
            }
        }
        return $dependencies;
    }
}