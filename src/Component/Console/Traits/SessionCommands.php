<?php

declare(strict_types=1);

namespace Strux\Component\Console\Traits;

use Exception;
use Strux\Component\Config\Config;
use Strux\Component\Database\ORM\Dialect\MySqlDialect;
use Strux\Component\Database\ORM\Dialect\PostgresDialect;
use Strux\Component\Database\ORM\Dialect\SqlDialect;
use Strux\Component\Database\ORM\Dialect\SqliteDialect;
use Strux\Component\Database\ORM\Dialect\SqlServerDialect;

trait SessionCommands
{
    abstract protected function initTable(string $table, string $sql, bool $verbose, ?string $checkDir = null, string $componentName = 'Table'): void;

    private function getSessionTable(): string
    {
        try {
            $config = $this->container->get(Config::class);
            return $config->get('session.table') ?? '_sessions';
        } catch (Exception $e) {
            return '_sessions';
        }
    }

    private function initSession(bool $verbose = false): void
    {
        $table = $this->getSessionTable();

        $driver = $this->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        /** @var SqlDialect $dialect */
        $dialect = match ($driver) {
            'mysql' => new MySqlDialect(),
            'pgsql' => new PostgresDialect(),
            'sqlite' => new SqliteDialect(),
            'sqlsrv' => new SqlServerDialect(),
            default => throw new Exception("Unsupported database driver: $driver"),
        };

        $columns = [
            "`id` VARCHAR(255) NOT NULL PRIMARY KEY",
            "`user_id` BIGINT UNSIGNED NULL",
            "`ip_address` VARCHAR(45) NULL",
            "`user_agent` TEXT NULL",
            "`payload` LONGTEXT NOT NULL",
            "`last_activity` INT NOT NULL"
        ];

        // Indexes are typically added via ALTER TABLE in some dialects or inside CREATE TABLE.
        // For simplicity across dialects, we just execute the dialect's create method.
        // We can append standard SQL indexes manually for now if the dialect supports it, or let the framework abstraction handle it.
        // In Mysql and Sqlite we can just append them to the columns.
        if ($driver === 'mysql' || $driver === 'sqlite') {
            $columns[] = "INDEX `sessions_user_id_index` (`user_id`)";
            $columns[] = "INDEX `sessions_last_activity_index` (`last_activity`)";
        }

        $sql = $dialect->buildCreateTableQuery($table, $columns);

        if ($driver === 'pgsql' || $driver === 'sqlsrv') {
            // Need to create indexes separately for these dialects
            $indexSql1 = "CREATE INDEX IF NOT EXISTS sessions_user_id_index ON {$table} (user_id)";
            $indexSql2 = "CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON {$table} (last_activity)";
            $sql .= "; " . $indexSql1 . "; " . $indexSql2 . ";";
        }

        $this->initTable($table, $sql, $verbose, null, 'Session');
    }
}