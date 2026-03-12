<?php

declare(strict_types=1);

namespace Strux\Component\Queue;

use Exception;
use PDO;
use Strux\Component\Config\Config;
use Throwable;

class Queue implements QueueInterface
{
    private Config $config;
    private PDO $db;

    public function __construct(Config $config, PDO $db)
    {
        $this->config = $config;
        $this->db = $db;
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

        $stmt = $this->db->prepare(
            "INSERT INTO $table (queue, payload, attempts, reserved_at, available_at, created_at)
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

            $sql = "
                SELECT * FROM $table
                WHERE queue = :queue 
                  AND (
                      (reserved_at IS NULL AND available_at <= :now) 
                      OR 
                      (reserved_at <= :retry_limit)
                  )
                ORDER BY id ASC
                LIMIT 1
                FOR UPDATE SKIP LOCKED
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':queue' => $queueName,
                ':now' => $now,
                ':retry_limit' => $retryLimit
            ]);

            $jobRecord = $stmt->fetch(PDO::FETCH_OBJ);

            if ($jobRecord) {
                $updateSql = "
                    UPDATE $table 
                    SET reserved_at = :now, attempts = attempts + 1 
                    WHERE id = :id
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
        $table = $this->getConnectionConfig()['table'] ?? 'jobs';
        $stmt = $this->db->prepare("DELETE FROM $table WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    public function release(int|string $id, int $delaySeconds = 0): void
    {
        $table = $this->getConnectionConfig()['table'] ?? 'jobs';
        $availableAt = time() + $delaySeconds;

        $stmt = $this->db->prepare("
            UPDATE $table 
            SET reserved_at = NULL, available_at = :available_at 
            WHERE id = :id
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
            $stmt = $this->db->prepare(
                "INSERT INTO {$failedTable} (connection, queue, payload, exception, failed_at) 
                 VALUES (:connection, :queue, :payload, :exception, :failed_at)"
            );

            $stmt->execute([
                ':connection' => $this->config->get('queue.default', 'database'),
                ':queue' => $jobRecord->queue,
                ':payload' => $jobRecord->payload,
                ':exception' => (string)$exception,
                ':failed_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            error_log("Could not write to failed_jobs table: " . $e->getMessage());
        }
    }
}