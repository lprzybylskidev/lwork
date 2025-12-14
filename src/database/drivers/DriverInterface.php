<?php declare(strict_types=1);

namespace src\database\drivers;

/**
 * @package src\database\drivers
 */
interface DriverInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function connect(array $config): \PDO;
}
