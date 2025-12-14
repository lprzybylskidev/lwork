<?php declare(strict_types=1);

namespace src\database;

/**
 * @package src\database
 */
final class DatabaseSchemaGuard
{
    /**
     * @param DatabaseManager $manager
     * @param DatabaseConfigLoader $loader
     */
    public function __construct(
        private DatabaseManager $manager,
        private DatabaseConfigLoader $loader,
    ) {}

    /**
     * @param array<int, string> $tables
     */
    public function ensureTables(array $tables): void
    {
        $connection = $this->manager->connection(
            $this->loader->defaultConnection(),
        );

        $driver = strtolower(
            (string) $connection->getAttribute(\PDO::ATTR_DRIVER_NAME),
        );

        foreach ($tables as $table) {
            if (!$this->tableExists($connection, $driver, $table)) {
                throw new \RuntimeException(
                    sprintf(
                        'Required table "%s" is missing in database "%s".',
                        $table,
                        $this->loader->defaultConnection(),
                    ),
                );
            }
        }
    }

    /**
     * @param \PDO $connection
     * @param string $driver
     * @param string $table
     * @return bool
     */
    private function tableExists(
        \PDO $connection,
        string $driver,
        string $table,
    ): bool {
        if (!preg_match('/^[a-z0-9_]+$/i', $table)) {
            return false;
        }

        if ($driver === 'sqlite') {
            $stmt = $connection->prepare(
                'SELECT name FROM sqlite_master WHERE type = \'table\' AND name = :name',
            );
            $stmt->execute([':name' => $table]);
            return (bool) $stmt->fetchColumn();
        }

        $quoted = $this->quoteIdentifier($driver, $table);

        try {
            $query = sprintf('SELECT 1 FROM %s LIMIT 1', $quoted);
            $connection->query($query);
            return true;
        } catch (\PDOException) {
            return false;
        }
    }

    /**
     * @param string $driver
     * @param string $table
     */
    private function quoteIdentifier(string $driver, string $table): string
    {
        return match ($driver) {
            'pgsql' => '"' . $table . '"',
            'mysql' => '`' . $table . '`',
            'sqlsrv', 'dblib' => '[' . $table . ']',
            default => $table,
        };
    }
}
