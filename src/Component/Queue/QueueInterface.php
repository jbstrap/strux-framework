<?php

declare(strict_types=1);

namespace Strux\Component\Queue;

use Throwable;

interface QueueInterface
{
    /**
     * Push a new job onto the queue.
     *
     * @param object $job
     * @param string|null $queue
     * @return void
     */
    public function push(object $job, ?string $queue = null): void;

    /**
     * Safely pop the next available job off the queue.
     *
     * @param string|null $queue
     * @return object|null
     */
    public function pop(?string $queue = null): ?object;

    /**
     * Delete a job from the queue after successful execution.
     *
     * @param int|string $id
     * @return void
     */
    public function delete(int|string $id): void;

    /**
     * Release a failed job back onto the queue to be retried later.
     *
     * @param int|string $id
     * @param int $delaySeconds
     * @return void
     */
    public function release(int|string $id, int $delaySeconds = 0): void;

    /**
     * Log a permanently failed job.
     *
     * @param object $jobRecord
     * @param Throwable $exception
     * @return void
     */
    public function fail(object $jobRecord, Throwable $exception): void;
}