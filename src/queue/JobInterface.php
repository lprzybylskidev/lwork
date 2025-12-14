<?php declare(strict_types=1);

namespace src\queue;

/**
 * @package src\queue
 */
interface JobInterface
{
    /**
     * @param array<string, mixed> $payload
     * @return void
     */
    public function handle(array $payload): void;
}
