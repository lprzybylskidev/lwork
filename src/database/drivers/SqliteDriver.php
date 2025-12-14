<?php declare(strict_types=1);

namespace src\database\drivers;

/**
 * @package src\database\drivers
 */
final class SqliteDriver implements DriverInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function connect(array $config): \PDO
    {
        $database = $config['database'] ?? ':memory:';
        $dsn = $config['dsn'] ?? sprintf('sqlite:%s', $database);
        $options = $this->normalizeOptions($config['options'] ?? []);

        $pdo = new \PDO($dsn, '', '', $options);
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        return $pdo;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return array<int, mixed>
     */
    private function normalizeOptions(array $options): array
    {
        return $options;
    }
}
