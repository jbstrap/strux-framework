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
    protected ?LoggerInterface $logger; // Make logger available

    // Config can override default PDO options
    private array $defaultPdoOptions = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Consistent with your DI
        PDO::ATTR_EMULATE_PREPARES => false,
        // PDO::ATTR_PERSISTENT => false, // Generally avoid persistent unless a specific need
    ];

    /**
     * @throws DatabaseException
     */
    public function __construct(Config $config, ?LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;

        // Connection is established on first call to getConnection() (lazy loading)
        // or can be established here if preferred. For simplicity, let's connect here.
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

            // Merge global PDO options with connection-specific and default options
            $pdoOptions = array_replace(
                $this->defaultPdoOptions,
                $this->config->get('database.global_options', []),
                $connectionConfig['options'] ?? []
            );
            // Ensure fetch mode from global etc is prioritized if set
            if ($globalFetch = $this->config->get('database.fetch')) {
                $pdoOptions[PDO::ATTR_DEFAULT_FETCH_MODE] = $globalFetch;
            }

            $dsn = $this->buildDsn($driver, $connectionConfig);
            $username = $connectionConfig['username'] ?? null;
            $password = $connectionConfig['password'] ?? null;

            $this->logger?->info("Attempting to connect to database.", ['driver' => $driver, 'dsn_preview' => preg_replace('/password=[^;]*/i', 'password=***', $dsn)]);

            $this->connection = new PDO($dsn, $username, $password, $pdoOptions);

            // Driver-specific post-connection setup
            if ($driver === 'sqlite') {
                if ($connectionConfig['foreign_key_constraints'] ?? false) {
                    $this->connection->exec('PRAGMA foreign_keys = ON;');
                    $this->logger?->info("SQLite: PRAGMA foreign_keys = ON executed.");
                }
            } elseif ($driver === 'mysql') {
                if (!empty($connectionConfig['charset'])) {
                    $this->connection->exec("SET NAMES '{$connectionConfig['charset']}'" .
                        (!empty($connectionConfig['collation']) ? " COLLATE '{$connectionConfig['collation']}'" : ''));
                    $this->logger?->info("MySQL: SET NAMES executed.", ['charset' => $connectionConfig['charset'], 'collation' => $connectionConfig['collation'] ?? null]);
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
                if (empty($config['host']) || empty($config['database'])) {
                    throw new InvalidArgumentException("MySQL connection requires 'host' and 'database' in etc.");
                }
                $dsn = "mysql:host={$config['host']};dbname={$config['database']}";
                if (!empty($config['port'])) {
                    $dsn .= ";port={$config['port']}";
                }
                if (!empty($config['charset'])) {
                    $dsn .= ";charset={$config['charset']}";
                }
                return $dsn;

            case 'sqlite':
                $dbPath = $config['path'] ?? null; // Path from etc (e.g., 'var/database/src.db')

                // Allow DB_DSN from env to override everything for SQLite for maximum flexibility
                $dbDsnFromEnv = env('DB_DSN');
                if ($dbDsnFromEnv && str_starts_with($dbDsnFromEnv, 'sqlite:')) {
                    $this->logger?->info("Using DB_DSN from environment for SQLite.", ['dsn' => $dbDsnFromEnv]);
                    // Ensure the directory for a DSN path exists if it's a file path
                    $envPath = substr($dbDsnFromEnv, 7); // Remove "sqlite:"
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

                // Resolve a path: if not absolute, assume relative to ROOT_PATH
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
            if (!mkdir($directory, 0775, true) && !is_dir($directory)) { // true for recursive
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
            // This would typically be an error if establishConnection wasn't called in constructor
            // or if it failed silently. For robustness, we can try to establish it here.
            $this->logger?->warning("PDO connection was null, attempting to re-establish.");
            $this->establishConnection(); // Or throw an exception if it should always be ready
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
//        $this->closeConnection();
    }
}
