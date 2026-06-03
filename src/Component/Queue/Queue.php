<?php

declare(strict_types=1);

namespace Strux\Component\Queue;

use Exception;
use PDO;
use Strux\Component\Config\Config;
use Strux\Component\Database\ORM\Dialect\MySqlDialect;
use Strux\Component\Database\ORM\Dialect\PostgresDialect;
use Strux\Component\Database\ORM\Dialect\SqlDialect;
use Strux\Component\Database\ORM\Dialect\SqliteDialect;
use Strux\Component\Database\ORM\Dialect\SqlServerDialect;
use Throwable;

class Queue implements QueueInterface
{
    private Config $config;
    private PDO $db;
    private SqlDialect $dialect;

    public function __construct(Config $config, PDO $db)
    {
        $this->config = $config;
        $this->db = $db;

        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $this->dialect = match ($driver) {
            'mysql' => new MySqlDialect(),
            'pgsql' => new PostgresDialect(),
            'sqlite' => new SqliteDialect(),
            'sqlsrv' => new SqlServerDialect(),
            default => throw new Exception("Unsupported database driver: $driver")
        };
    }

    protected function getConnectionConfig(): array
    {
        $default = $this->config->get('queue.default', 'database');
        return $this->config->get("queue.connections.{$default}", []);
    }

    public function push(object $job, ?string $queue = null): void
    {
        $connConfig = $this->getConnectionConfig();
        $queueName = $queue ?? $connConfig['queue'] ?? 'default';
        $table = $connConfig['table'] ?? 'jobs';

        $payload = json_encode([
            'displayName' => get_class($job),
            'data' => [
                'commandName' => get_class($job),
                'command' => serialize(clone $job),
            ]
        ]);

        $quotedTable = $this->dialect->quoteTable($table);
        $qCol = $this->dialect->quote('queue');
        $pCol = $this->dialect->quote('payload');
        $aCol = $this->dialect->quote('attempts');
        $rCol = $this->dialect->quote('reserved_at');
        $avCol = $this->dialect->quote('available_at');
        $cCol = $this->dialect->quote('created_at');

        $stmt = $this->db->prepare(
            "INSERT INTO $quotedTable ($qCol, $pCol, $aCol, $rCol, $avCol, $cCol)
             VALUES (:queue, :payload, 0, null, :available_at, :created_at)"
        );

        $stmt->execute([
            ':queue' => $queueName,
            ':payload' => $payload,
            ':available_at' => time(),
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function pop(?string $queue = null): ?object
    {
        $connConfig = $this->getConnectionConfig();
        $queueName = $queue ?? $connConfig['queue'] ?? 'default';
        $table = $connConfig['table'] ?? 'jobs';
        $retryAfter = $connConfig['retry_after'] ?? 90;

        $this->db->beginTransaction();

        try {
            $now = time();
            $retryLimit = $now - $retryAfter;

            $quotedTable = $this->dialect->quoteTable($table);
            $qCol = $this->dialect->quote('queue');
            $rCol = $this->dialect->quote('reserved_at');
            $avCol = $this->dialect->quote('available_at');
            $idCol = $this->dialect->quote('id');

            $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
            
            if ($driver === 'sqlsrv') {
                $sql = "
                    SELECT TOP 1 * FROM $quotedTable WITH (UPDLOCK, READPAST)
                    WHERE $qCol = :queue 
                      AND (
                          ($rCol IS NULL AND $avCol <= :now) 
                          OR 
                          ($rCol <= :retry_limit)
                      )
                    ORDER BY $idCol ASC
                ";
            } elseif ($driver === 'sqlite') {
                $sql = "
                    SELECT * FROM $quotedTable
                    WHERE $qCol = :queue 
                      AND (
                          ($rCol IS NULL AND $avCol <= :now) 
                          OR 
                          ($rCol <= :retry_limit)
                      )
                    ORDER BY $idCol ASC
                    LIMIT 1
                ";
            } else {
                $sql = "
                    SELECT * FROM $quotedTable
                    WHERE $qCol = :queue 
                      AND (
                          ($rCol IS NULL AND $avCol <= :now) 
                          OR 
                          ($rCol <= :retry_limit)
                      )
                    ORDER BY $idCol ASC
                    LIMIT 1
                    FOR UPDATE SKIP LOCKED
                ";
            }

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':queue' => $queueName,
                ':now' => $now,
                ':retry_limit' => $retryLimit
            ]);

            $jobRecord = $stmt->fetch(PDO::FETCH_OBJ);

            if ($jobRecord) {
                $aCol = $this->dialect->quote('attempts');
                $updateSql = "
                    UPDATE $quotedTable 
                    SET $rCol = :now, $aCol = $aCol + 1 
                    WHERE $idCol = :id
                ";

                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute([
                    ':now' => $now,
                    ':id' => $jobRecord->id
                ]);

                $this->db->commit();
                return $jobRecord;
            }

            $this->db->commit();
            return null;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function delete(int|string $id): void
    {
        $quotedTable = $this->dialect->quoteTable($this->getConnectionConfig()['table'] ?? 'jobs');
        $idCol = $this->dialect->quote('id');
        $stmt = $this->db->prepare("DELETE FROM $quotedTable WHERE $idCol = :id");
        $stmt->execute([':id' => $id]);
    }

    public function release(int|string $id, int $delaySeconds = 0): void
    {
        $quotedTable = $this->dialect->quoteTable($this->getConnectionConfig()['table'] ?? 'jobs');
        $availableAt = time() + $delaySeconds;
        $rCol = $this->dialect->quote('reserved_at');
        $avCol = $this->dialect->quote('available_at');
        $idCol = $this->dialect->quote('id');

        $stmt = $this->db->prepare("
            UPDATE $quotedTable 
            SET $rCol = NULL, $avCol = :available_at 
            WHERE $idCol = :id
        ");

        $stmt->execute([
            ':available_at' => $availableAt,
            ':id' => $id
        ]);
    }

    public function fail(object $jobRecord, Throwable $exception): void
    {
        $failedConfig = $this->config->get('queue.failed', []);
        $failedTable = $failedConfig['table'] ?? 'failed_jobs';

        try {
            $quotedFailedTable = $this->dialect->quoteTable($failedTable);
            $connCol = $this->dialect->quote('connection');
            $qCol = $this->dialect->quote('queue');
            $pCol = $this->dialect->quote('payload');
            $excCol = $this->dialect->quote('exception');
            $fCol = $this->dialect->quote('failed_at');

            $stmt = $this->db->prepare(
                "INSERT INTO $quotedFailedTable ($connCol, $qCol, $pCol, $excCol, $fCol) 
                 VALUES (:connection, :queue, :payload, :exception, :failed_at)"
            );

            $stmt->execute([
                ':connection' => $this->config->get('queue.default', 'database'),
                ':queue' => $jobRecord->queue,
                ':payload' => $jobRecord->payload,
                ':exception' => (string)$exception,
                ':failed_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Could not write to failed_jobs table: " . $e->getMessage());
        }
    }
}