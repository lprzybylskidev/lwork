<?php declare(strict_types=1);

namespace src\bootstrap\kernels;

/**
 * @package src\bootstrap\kernels
 */
interface KernelInterface
{
    /**
     * @return int
     */
    public function handle(): int;

    /**
     * @param \Throwable $e
     * @return int
     */
    public function handleException(\Throwable $e): int;
}
