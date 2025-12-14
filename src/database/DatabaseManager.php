<?php declare(strict_types=1);

namespace src\database;

use src\database\drivers\DriverInterface;

/**
 * @package src\database
 */
final class DatabaseManager
{
    /** @var array<string, array<string, mixed>> */
    private array $configs;

    /** @var array<string, DriverInterface> */
    private array $drivers = [];

    /** @var array<string, \PDO> */
    private array $connections = [];

    /**
     * @param array<string, array<string, mixed>> $configs
     */
    public function __construct(array $configs)
    {
        $this->configs = $configs;
    }

    /**
     * @param string $name
     * @param DriverInterface $driver
     */
    public function registerDriver(string $name, DriverInterface $driver): void
    {
        $this->drivers[strtolower($name)] = $driver;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasConnection(string $name): bool
    {
        return array_key_exists(strtolower($name), $this->configs);
    }

    /**
     * @param string $name
     * @return \PDO
     */
    public function connection(string $name): \PDO
    {
        $normalized = strtolower($name);

        if (isset($this->connections[$normalized])) {
            return $this->connections[$normalized];
        }

        $config = $this->configs[$normalized] ?? null;

        if ($config === null) {
            throw new \RuntimeException(
                sprintf('Database connection "%s" is not configured.', $name),
            );
        }

        $driverName = $config['driver'] ?? '';
        $driver = $this->drivers[strtolower($driverName)] ?? null;

        if ($driver === null) {
            throw new \RuntimeException(
                sprintf('Database driver "%s" is not registered.', $driverName),
            );
        }

        $pdo = $driver->connect($config);
        $this->connections[$normalized] = $pdo;

        return $pdo;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function configs(): array
    {
        return $this->configs;
    }
}
