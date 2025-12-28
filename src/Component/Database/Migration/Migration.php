<?php

namespace Strux\Component\Database\Migration;

use PDO;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Strux\Component\Exceptions\Container\ContainerException;
use Strux\Support\ContainerBridge;
use Throwable;

abstract class Migration
{
    protected ?PDO $db;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ContainerException
     */
    public function __construct()
    {
        // We resolve the ?\PDO connection from the Container manually
        // or assume it's injected. For simplicity in generated files,
        // we can grab it globally or expect it to be set.

        // However, the easiest way for generated files is to use the
        // App container singleton pattern if available,
        // OR rely on the runner to set it.

        // Let's use the Container to fetch it cleanly:
        $this->db = ContainerBridge::resolve(PDO::class);
    }

    abstract public function up(): void;

    abstract public function down(): void;

    /**
     * Safely executes a list of SQL queries with Foreign Key checks disabled.
     * Logs execution to the console.
     * @throws Throwable
     */
    protected function executeQueries(array $queries): void
    {
        if (empty($queries)) {
            return;
        }

        // Disable foreign key checks to allow modifications to constrained columns
        $this->db->exec('SET FOREIGN_KEY_CHECKS=0;');

        foreach ($queries as $query) {
            // Skip comments
            if (str_starts_with(trim($query), '--')) {
                continue;
            }

            echo "\033[32mExecuting:\033[0m $query" . PHP_EOL;

            try {
                $this->db->exec($query);
            } catch (Throwable $e) {
                // Ensure we re-enable checks even if a query fails
                $this->db->exec('SET FOREIGN_KEY_CHECKS=1;');
                throw $e;
            }
        }

        // Re-enable foreign key checks
        $this->db->exec('SET FOREIGN_KEY_CHECKS=1;');
    }
}