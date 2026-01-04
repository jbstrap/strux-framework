<?php

declare(strict_types=1);

namespace Strux\Component\Session;

use PDO;
use PDOException;
use Psr\Http\Message\ServerRequestInterface;
use SessionHandlerInterface;
use Strux\Auth\AuthManager;
use Strux\Component\Exceptions\DatabaseException;

class DatabaseSessionHandler implements SessionHandlerInterface
{
    private PDO $db;
    private string $table;
    private AuthManager $auth;
    private ServerRequestInterface $request;

    public function __construct(PDO $pdo, string $table, AuthManager $auth, ServerRequestInterface $request)
    {
        $this->db = $pdo;
        $this->table = $table;
        $this->auth = $auth;
        $this->request = $request;
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
            $stmt = $this->db->prepare("SELECT payload FROM `{$this->table}` WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Return empty string if no session found (standard behavior)
            // returning false usually indicates an error to PHP
            return $result['payload'] ?? '';
        } catch (PDOException $e) {
            // Log error but don't throw to prevent white screen on session start?
            // Standard practice is to let PHP handle session errors or throw custom.
            throw new DatabaseException("Database Session read error: " . $e->getMessage());
        }
    }

    /**
     * @throws DatabaseException
     */
    public function write(string $id, string $data): bool
    {
        // 1. Get User ID safely
        // We use the 'web' sentinel explicitly as sessions are primarily for web auth.
        // We suppress errors or check null because Auth might not be fully booted
        // or user might be guest during early session writes.
        $userId = null;
        try {
            if ($this->auth->sentinel('web')->check()) {
                $userId = $this->auth->sentinel('web')->id();
            }
        } catch (\Throwable $e) {
            // Ignore auth errors during session write (e.g. if auth service isn't ready)
        }

        // 2. Get Request Metadata
        $serverParams = $this->request->getServerParams();
        $ipAddress = $serverParams['REMOTE_ADDR'] ?? null;
        $userAgent = $this->request->getHeaderLine('User-Agent');
        $userAgent = substr($userAgent, 0, 255); // Truncate to fit typical column
        $access = time();

        try {
            // 3. Upsert Session
            // MySQL/MariaDB syntax:
            $stmt = $this->db->prepare(
                "INSERT INTO `{$this->table}` (id, user_id, ip_address, user_agent, payload, last_activity) 
                 VALUES (:id, :user_id, :ip_address, :user_agent, :payload, :access)
                 ON DUPLICATE KEY UPDATE 
                    user_id = VALUES(user_id),
                    ip_address = VALUES(ip_address),
                    user_agent = VALUES(user_agent),
                    payload = VALUES(payload),
                    last_activity = VALUES(last_activity)"
            );

            // For SQLite, use "INSERT OR REPLACE INTO..."
            // Logic to detect driver and switch syntax might be needed if supporting both.
            // Assuming MySQL based on previous context.

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
            $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE id = :id");
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
            $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE last_activity < :old");
            $stmt->execute([':old' => $old]);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new DatabaseException("Database Session garbage collection error: " . $e->getMessage());
        }
    }
}