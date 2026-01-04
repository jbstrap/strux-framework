<?php

declare(strict_types=1);

namespace Strux\Component\Database\Migration;

use PDO;
use RuntimeException;

class Schema
{
    private static ?PDO $db = null;

    public static function setConnection(PDO $pdo): void
    {
        self::$db = $pdo;
    }

    /**
     * Create a new database table using the fluent TableBlueprint.
     */
    public static function create(string $tableName, callable $callback): void
    {
        if (self::$db === null) {
            throw new RuntimeException("Database connection not set for Schema.");
        }

        // Use TableBlueprint for manual schema definition
        $builder = new TableBlueprint($tableName);

        // Run the user's callback (e.g., $table->string('email'))
        $callback($builder);

        // Generate the SQL
        $sql = $builder->build();

        // Debug output (optional)
        // echo "Executing: $sql\n";

        self::$db->exec($sql);
    }

    public static function dropIfExists(string $tableName): void
    {
        if (self::$db === null) {
            throw new RuntimeException("Database connection not set for Schema.");
        }
        self::$db->exec("DROP TABLE IF EXISTS `{$tableName}`");
    }

    public static function execute(string $sql): void
    {
        if (self::$db === null) {
            throw new RuntimeException("Database connection not set for Schema.");
        }
        self::$db->exec($sql);
    }
}