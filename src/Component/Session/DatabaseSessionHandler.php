<?php

declare(strict_types=1);

namespace Strux\Component\Session;

use PDO;
use PDOException;
use Psr\Http\Message\ServerRequestInterface;
use SessionHandlerInterface;
use Strux\Auth\AuthManager;
use Strux\Component\Database\ORM\Dialect\MySqlDialect;
use Strux\Component\Database\ORM\Dialect\PostgresDialect;
use Strux\Component\Database\ORM\Dialect\SqlDialect;
use Strux\Component\Database\ORM\Dialect\SqliteDialect;
use Strux\Component\Database\ORM\Dialect\SqlServerDialect;
use Strux\Component\Exceptions\DatabaseException;

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $db;
    private string $table;
    private AuthManager $auth;
    private ServerRequestInterface $request;
    private SqlDialect $dialect;

    public function __construct(PDO $pdo, string $table, AuthManager $auth, ServerRequestInterface $request)
    {
        $this->db = $pdo;
        $this->table = $table;
        $this->auth = $auth;
        $this->request = $request;

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->dialect = match ($driver) {
            'mysql' => new MySqlDialect(),
            'pgsql' => new PostgresDialect(),
            'sqlite' => new SqliteDialect(),
            'sqlsrv' => new SqlServerDialect(),
            default => throw new \Exception("Unsupported database driver: $driver")
        };
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    /**
     * @throws DatabaseException
     */
    public function read(string $id): string|false
    {
        try {
            $quotedTable = $this->dialect->quoteTable($this->table);
            $payloadCol = $this->dialect->quote('payload');
            $idCol = $this->dialect->quote('id');

            $stmt = $this->db->prepare("SELECT $payloadCol FROM $quotedTable WHERE $idCol = :id");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['payload'] ?? '';
        } catch (PDOException $e) {
            throw new DatabaseException("Database Session read error: " . $e->getMessage());
        }
    }

    /**
     * @throws DatabaseException
     */
    public function write(string $id, string $data): bool
    {
        $userId = null;
        try {
            if ($this->auth->sentinel('web')->check()) {
                $userId = $this->auth->sentinel('web')->id();
            }
        } catch (\Throwable $e) {
        }

        $serverParams = $this->request->getServerParams();
        $ipAddress = $serverParams['REMOTE_ADDR'] ?? null;
        $userAgent = $this->request->getHeaderLine('User-Agent');
        $userAgent = substr($userAgent, 0, 255);
        $access = time();

        try {
            $quotedTable = $this->dialect->quoteTable($this->table);
            $idCol = $this->dialect->quote('id');
            $userIdCol = $this->dialect->quote('user_id');
            $ipCol = $this->dialect->quote('ip_address');
            $uaCol = $this->dialect->quote('user_agent');
            $payloadCol = $this->dialect->quote('payload');
            $activityCol = $this->dialect->quote('last_activity');

            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            if ($driver === 'mysql') {
                $sql = "INSERT INTO $quotedTable ($idCol, $userIdCol, $ipCol, $uaCol, $payloadCol, $activityCol) 
                     VALUES (:id, :user_id, :ip_address, :user_agent, :payload, :access)
                     ON DUPLICATE KEY UPDATE 
                        $userIdCol = VALUES($userIdCol),
                        $ipCol = VALUES($ipCol),
                        $uaCol = VALUES($uaCol),
                        $payloadCol = VALUES($payloadCol),
                        $activityCol = VALUES($activityCol)";
            } elseif ($driver === 'sqlite') {
                $sql = "INSERT OR REPLACE INTO $quotedTable ($idCol, $userIdCol, $ipCol, $uaCol, $payloadCol, $activityCol) 
                     VALUES (:id, :user_id, :ip_address, :user_agent, :payload, :access)";
            } elseif ($driver === 'pgsql') {
                $sql = "INSERT INTO $quotedTable ($idCol, $userIdCol, $ipCol, $uaCol, $payloadCol, $activityCol) 
                     VALUES (:id, :user_id, :ip_address, :user_agent, :payload, :access)
                     ON CONFLICT ($idCol) DO UPDATE SET 
                        $userIdCol = EXCLUDED.$userIdCol,
                        $ipCol = EXCLUDED.$ipCol,
                        $uaCol = EXCLUDED.$uaCol,
                        $payloadCol = EXCLUDED.$payloadCol,
                        $activityCol = EXCLUDED.$activityCol";
            } elseif ($driver === 'sqlsrv') {
                $sql = "MERGE INTO $quotedTable WITH (HOLDLOCK) AS target
                     USING (SELECT :id AS id) AS source
                     ON target.$idCol = source.id
                     WHEN MATCHED THEN
                        UPDATE SET 
                           $userIdCol = :user_id,
                           $ipCol = :ip_address,
                           $uaCol = :user_agent,
                           $payloadCol = :payload,
                           $activityCol = :access
                     WHEN NOT MATCHED THEN
                        INSERT ($idCol, $userIdCol, $ipCol, $uaCol, $payloadCol, $activityCol)
                        VALUES (:id, :user_id, :ip_address, :user_agent, :payload, :access);";
            } else {
                $sql = "INSERT INTO $quotedTable ($idCol, $userIdCol, $ipCol, $uaCol, $payloadCol, $activityCol) 
                     VALUES (:id, :user_id, :ip_address, :user_agent, :payload, :access)";
            }

            $stmt = $this->db->prepare($sql);

            return $stmt->execute([
                ':id' => $id,
                ':user_id' => $userId,
                ':ip_address' => $ipAddress,
                ':user_agent' => $userAgent,
                ':payload' => $data,
                ':access' => $access,
            ]);
        } catch (PDOException $e) {
            throw new DatabaseException("Database Session write error: " . $e->getMessage());
        }
    }

    /**
     * @throws DatabaseException
     */
    public function destroy(string $id): bool
    {
        try {
            $quotedTable = $this->dialect->quoteTable($this->table);
            $idCol = $this->dialect->quote('id');

            $stmt = $this->db->prepare("DELETE FROM $quotedTable WHERE $idCol = :id");
            $stmt->execute([':id' => $id]);
            return true;
        } catch (PDOException $e) {
            throw new DatabaseException("Database Session destroy error: " . $e->getMessage());
        }
    }

    /**
     * @throws DatabaseException
     */
    public function gc(int $max_lifetime): int|false
    {
        $old = time() - $max_lifetime;
        try {
            $quotedTable = $this->dialect->quoteTable($this->table);
            $activityCol = $this->dialect->quote('last_activity');

            $stmt = $this->db->prepare("DELETE FROM $quotedTable WHERE $activityCol < :old");
            $stmt->execute([':old' => $old]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new DatabaseException("Database Session garbage collection error: " . $e->getMessage());
        }
    }
}