<?php declare(strict_types=1);

namespace src\session;

use SessionHandlerInterface;

/**
 * @package src\session
 */
final class DatabaseSessionHandler implements SessionHandlerInterface
{
    /**
     * @param \PDO $connection
     * @param int $lifetime
     * @param string $table
     */
    public function __construct(
        private \PDO $connection,
        private int $lifetime,
        private string $table = 'sessions',
    ) {
        $this->ensureTableName();
    }

    /**
     * @param string $savePath
     * @param string $name
     * @return bool
     */
    /**
     * @param string $savePath
     * @param string $name
     * @return bool
     */
    public function open(string $savePath, string $name): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    /**
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * @param string $sessionId
     * @return string
     */
    /**
     * @param string $sessionId
     * @return string
     */
    public function read(string $sessionId): string
    {
        $stmt = $this->connection->prepare(
            sprintf(
                'SELECT payload FROM %s WHERE id = :id AND (expires_at IS NULL OR expires_at > :now)',
                $this->quoteIdentifier(),
            ),
        );
        $now = $this->nowString();
        $stmt->execute([':id' => $sessionId, ':now' => $now]);

        $value = $stmt->fetchColumn();

        if ($value === false) {
            return '';
        }

        return (string) $value;
    }

    /**
     * @param string $sessionId
     * @param string $data
     * @return bool
     */
    /**
     * @param string $sessionId
     * @param string $data
     * @return bool
     */
    public function write(string $sessionId, string $data): bool
    {
        $expires =
            $this->lifetime > 0 ? $this->futureString($this->lifetime) : null;
        $now = $this->nowString();

        $this->connection
            ->prepare(
                sprintf(
                    'DELETE FROM %s WHERE id = :id',
                    $this->quoteIdentifier(),
                ),
            )
            ->execute([':id' => $sessionId]);

        $insert = sprintf(
            'INSERT INTO %s (id, payload, expires_at, created_at) VALUES (:id, :payload, :expires, :created)',
            $this->quoteIdentifier(),
        );

        $stmt = $this->connection->prepare($insert);
        return $stmt->execute([
            ':id' => $sessionId,
            ':payload' => $data,
            ':expires' => $expires,
            ':created' => $now,
        ]);
    }

    /**
     * @param string $sessionId
     * @return bool
     */
    /**
     * @param string $sessionId
     * @return bool
     */
    public function destroy(string $sessionId): bool
    {
        $stmt = $this->connection->prepare(
            sprintf('DELETE FROM %s WHERE id = :id', $this->quoteIdentifier()),
        );

        return $stmt->execute([':id' => $sessionId]);
    }

    /**
     * @param int $maxLifetime
     * @return int|false
     */
    /**
     * @param int $maxLifetime
     * @return int|false
     */
    public function gc(int $maxLifetime): int|false
    {
        $stmt = $this->connection->prepare(
            sprintf(
                'DELETE FROM %s WHERE expires_at IS NOT NULL AND expires_at <= :now',
                $this->quoteIdentifier(),
            ),
        );

        $stmt->execute([':now' => $this->nowString()]);

        return $stmt->rowCount();
    }

    /**
     * @return void
     */
    private function ensureTableName(): void
    {
        if (!preg_match('/^[a-z0-9_]+$/i', $this->table)) {
            throw new \RuntimeException('Invalid session table name.');
        }
    }

    /**
     * @return string
     */
    private function quoteIdentifier(): string
    {
        return $this->table;
    }

    /**
     * @return string
     */
    private function nowString(): string
    {
        return (new \DateTimeImmutable(
            'now',
            new \DateTimeZone('UTC'),
        ))->format('Y-m-d H:i:s');
    }

    /**
     * @param int $seconds
     * @return string
     */
    private function futureString(int $seconds): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->add(new \DateInterval('PT' . $seconds . 'S'))
            ->format('Y-m-d H:i:s');
    }
}
