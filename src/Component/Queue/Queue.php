<?php

declare(strict_types=1);

namespace Strux\Component\Queue;

use PDO;
use Strux\Component\Config\Config;

class Queue
{
    private Config $config;
    private PDO $db;

    public function __construct(Config $config, PDO $db)
    {
        $this->config = $config;
        $this->db = $db;
    }

    public function push(object $job, ?string $queue = null): void
    {
        $queueName = $queue ?? $this->config->get('queue.connections.database.queue', 'default');
        $payload = json_encode([
            'displayName' => get_class($job),
            'data' => [
                'commandName' => get_class($job),
                'command' => serialize(clone $job),
            ]
        ]);

        $stmt = $this->db->prepare(
            'INSERT INTO jobs (queue, payload, attempts, reserved_at, available_at, created_at)
             VALUES (:queue, :payload, 0, null, :available_at, :created_at)'
        );

        $stmt->execute([
            ':queue' => $queueName,
            ':payload' => $payload,
            ':available_at' => time(),
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}