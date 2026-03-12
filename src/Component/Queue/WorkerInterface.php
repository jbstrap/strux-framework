<?php

declare(strict_types=1);

namespace Strux\Component\Queue;

interface WorkerInterface
{
    /**
     * Continuously poll the queue and process jobs.
     *
     * @param string $queueName
     * @return void
     */
    public function process(string $queueName = 'default'): void;
}