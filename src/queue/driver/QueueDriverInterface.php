<?php declare(strict_types=1);

namespace src\queue\driver;

use src\queue\QueueJob;

/**
 * @package src\queue\driver
 */
interface QueueDriverInterface
{
    /**
     * @param QueueJob $job
     * @return void
     */
    public function push(QueueJob $job): void;

    /**
     * @return QueueJob|null
     */
    public function pop(): ?QueueJob;
}
