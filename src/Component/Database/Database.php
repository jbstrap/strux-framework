<?php

declare(strict_types=1);

namespace Strux\Component\Database;

use InvalidArgumentException;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Strux\Component\Config\Config;
use Strux\Component\Exceptions\DatabaseException;

class Database
{
    protected ?PDO $connection = null;
    protected Config $config;
    protected ?LoggerInterface $logger;

    private array $defaultPdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    /**
     * @throws DatabaseException
     */
    public function __construct(Config $config, ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;

        $this->establishConnection();
    }

    /**
     * Establishes the database connection based on configuration.
     * @throws DatabaseException
     */
    protected function establishConnection(): void
    {
        if ($this->connection !== null) {
            return;
        }

        try {
            $defaultDriver = $this->config->get('database.default', 'sqlite');
            $connectionConfig = $this->config->get("database.connections.{$defaultDriver}");

            if (empty($connectionConfig)) {
                throw new InvalidArgumentException("Database configuration for default driver '{$defaultDriver}' not found.");
            }

            $driver = $connectionConfig['driver'] ?? $defaultDriver;

            $pdoOptions = array_replace(
                $this->defaultPdoOptions,
                $this->config->get('database.global_options', []),
                $connectionConfig['options'] ?? []
            );
            
            if ($globalFetch = $this->config->get('database.fetch')) {
                $pdoOptions[PDO::ATTR_DEFAULT_FETCH_MODE] = $globalFetch;
            }

            $dsn = $this->buildDsn($driver, $connectionConfig);
            $username = $connectionConfig['username'] ?? null;
            $password = $connectionConfig['password'] ?? null;

            $this->logger?->info("Attempting to connect to database.", [
                'driver' => $driver, 
                'dsn_preview' => preg_replace('/password=[^;]*/i', 'password=***', $dsn)
            ]);

            $this->connection = new PDO($dsn, $username, $password, $pdoOptions);

            if ($driver === 'sqlite') {
                if ($connectionConfig['foreign_key_constraints'] ?? false) {
                    $this->connection->exec('PRAGMA foreign_keys = ON;');
                    $this->logger?->info("SQLite: PRAGMA foreign_keys = ON executed.");
                }
            } elseif ($driver === 'mysql' || $driver === 'mariadb') {
                if (!empty($connectionConfig['charset'])) {
                    $this->connection->exec("SET NAMES '{$connectionConfig['charset']}'" .
                        (!empty($connectionConfig['collation']) ? " COLLATE '{$connectionConfig['collation']}'" : ''));
                    $this->logger?->info("MySQL/MariaDB: SET NAMES executed.", ['charset' => $connectionConfig['charset'], 'collation' => $connectionConfig['collation'] ?? null]);
                }
            } elseif ($driver === 'pgsql') {
                if (!empty($connectionConfig['schema'])) {
                    $this->connection->exec("SET search_path TO '{$connectionConfig['schema']}'");
                    $this->logger?->info("PostgreSQL: search_path set.", ['schema' => $connectionConfig['schema']]);
                }
            }

            $this->logger?->info("Database connection established successfully.", ['driver' => $driver]);
        } catch (PDOException $e) {
            $this->logger?->critical("Database connection failed.", ['driver' => $driver ?? 'unknown', 'error' => $e->getMessage()]);
            throw new DatabaseException('Database connection failed: ' . $e->getMessage(), (int)$e->getCode(), $e);
        } catch (InvalidArgumentException $e) {
            $this->logger?->error("Database configuration error.", ['error' => $e->getMessage()]);
            throw new DatabaseException('Database configuration error: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Builds the DSN string for PDO.
     * @throws InvalidArgumentException if required, DSN parameters are missing.
     * @throws DatabaseException if SQLite path cannot be handled.
     */
    private function buildDsn(string $driver, array $config): string
    {
        switch ($driver) {
            case 'mysql':
            case 'mariadb':
                if (empty($config['host']) || empty($config['database'])) {
                    throw new InvalidArgumentException("MySQL/MariaDB connection requires 'host' and 'database' in etc.");
                }
                $dsn = "mysql:host={$config['host']};dbname={$config['database']}";
                if (!empty($config['port'])) {
                    $dsn .= ";port={$config['port']}";
                }
                if (!empty($config['charset'])) {
                    $dsn .= ";charset={$config['charset']}";
                }
                return $dsn;

            case 'pgsql':
                if (empty($config['host']) || empty($config['database'])) {
                    throw new InvalidArgumentException("PostgreSQL connection requires 'host' and 'database'.");
                }
                $dsn = "pgsql:host={$config['host']};dbname={$config['database']}";
                if (!empty($config['port'])) {
                    $dsn .= ";port={$config['port']}";
                }
                return $dsn;

            case 'sqlsrv':
                if (empty($config['host']) || empty($config['database'])) {
                    throw new InvalidArgumentException("SQL Server connection requires 'host' and 'database'.");
                }
                $dsn = "sqlsrv:Server={$config['host']}";
                if (!empty($config['port'])) {
                    $dsn .= ",{$config['port']}";
                }
                $dsn .= ";Database={$config['database']}";
                return $dsn;

            case 'sqlite':
                $dbPath = $config['path'] ?? null;
                $dbDsnFromEnv = env('DB_DSN');
                if ($dbDsnFromEnv && str_starts_with($dbDsnFromEnv, 'sqlite:')) {
                    $this->logger?->info("Using DB_DSN from environment for SQLite.", ['dsn' => $dbDsnFromEnv]);
                    $envPath = substr($dbDsnFromEnv, 7);
                    if ($envPath !== ':memory:') {
                        $this->ensureDirectoryExists(dirname($envPath));
                    }
                    return $dbDsnFromEnv;
                }

                if (empty($dbPath)) {
                    throw new InvalidArgumentException("SQLite connection requires 'path' in etc or DB_DSN in env.");
                }

                if ($dbPath === ':memory:') {
                    $this->logger?->info("Using in-memory SQLite database.");
                    return 'sqlite::memory:';
                }

                $actualDbPath = (str_starts_with($dbPath, '/') || preg_match('/^[a-zA-Z]:[\\\\\/]/', $dbPath) || defined('PHPUNIT_RUNNING'))
                    ? $dbPath
                    : rtrim(ROOT_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($dbPath, DIRECTORY_SEPARATOR);

                $this->ensureDirectoryExists(dirname($actualDbPath));
                $this->logger?->info("SQLite database path resolved.", ['path' => $actualDbPath]);
                return 'sqlite:' . $actualDbPath;

            default:
                throw new InvalidArgumentException("Unsupported database driver: {$driver}");
        }
    }

    /**
     * Ensures the specified directory exists, creating it if necessary.
     * @throws DatabaseException if the directory cannot be created.
     */
    private function ensureDirectoryExists(string $directory): void
    {
        if (!is_dir($directory)) {
            $this->logger?->info("Attempting to create directory.", ['directory' => $directory]);
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) {
                $this->logger?->error("Directory could not be created.", ['directory' => $directory]);
                throw new DatabaseException("Failed to create directory: {$directory}. Check permissions.");
            }
            $this->logger?->info("Directory created successfully.", ['directory' => $directory]);
        }
    }


    /**
     * @throws DatabaseException
     */
    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->logger?->warning("PDO connection was null, attempting to re-establish.");
            $this->establishConnection();
        }
        return $this->connection;
    }

    public function closeConnection(): void
    {
        if ($this->connection !== null) {
            $this->logger?->info("Closing database connection.");
            $this->connection = null;
        }
    }

    public function __destruct()
    {
        // $this->closeConnection();
    }
}
