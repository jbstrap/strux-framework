<?php

declare(strict_types=1);

namespace Strux\Component\Console\Traits;

use Exception;
use Strux\Component\Config\Config;

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

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
            `id` VARCHAR(255) NOT NULL PRIMARY KEY,
            `user_id` BIGINT UNSIGNED NULL,
            `ip_address` VARCHAR(45) NULL,
            `user_agent` TEXT NULL,
            `payload` LONGTEXT NOT NULL,
            `last_activity` INT NOT NULL,
            INDEX `sessions_user_id_index` (`user_id`),
            INDEX `sessions_last_activity_index` (`last_activity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        $this->initTable($table, $sql, $verbose, null, 'Session');
    }
}