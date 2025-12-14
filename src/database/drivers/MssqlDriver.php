<?php declare(strict_types=1);

namespace src\database\drivers;

/**
 * @package src\database\drivers
 */
final class MssqlDriver implements DriverInterface
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
        $port = $config['port'] ?? 1433;
        $database = $config['database'] ?? '';

        return sprintf(
            'sqlsrv:Server=%s,%d;Database=%s',
            $host,
            (int) $port,
            $database,
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
            $stmt = $pdo->prepare('SET TIME ZONE ?');
            $stmt->execute([$timezone]);
        } catch (\Throwable) {
            // best effort
        }
    }
}
