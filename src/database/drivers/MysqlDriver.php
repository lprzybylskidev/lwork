<?php declare(strict_types=1);

namespace src\database\drivers;

/**
 * @package src\database\drivers
 */
final class MysqlDriver implements DriverInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function connect(array $config): \PDO
    {
        $dsn = $config['dsn'] ?? $this->buildDsn($config);
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $options = $this->normalizeOptions($config['options'] ?? []);

        $pdo = new \PDO($dsn, $username, $password, $options);
        $this->applyTimezone($pdo, $config['timezone'] ?? null);

        return $pdo;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildDsn(array $config): string
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $host,
            (int) $port,
            $database,
            $charset,
        );
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

    /**
     * @param \PDO $pdo
     * @param string|null $timezone
     * @return void
     */
    private function applyTimezone(\PDO $pdo, ?string $timezone): void
    {
        if ($timezone === null || $timezone === '') {
            return;
        }

        try {
            $stmt = $pdo->prepare('SET time_zone = ?');
            $stmt->execute([$timezone]);
        } catch (\Throwable) {
            // best effort
        }
    }
}
