<?php declare(strict_types=1);

namespace src\database;

use src\config\ConfigManager;

/**
 * @package src\database
 */
final class DatabaseConfigLoader
{
    private array $connections;

    private string $default;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(private ConfigManager $configManager)
    {
        $data = $this->configManager->get('database', []);
        $connections = $data['connections'] ?? [];

        if ($connections === []) {
            throw new \RuntimeException('No database connections configured.');
        }

        $this->connections = $connections;
        $default = $data['default'] ?? array_key_first($connections);
        $defaultKey = $default !== null ? strtolower((string) $default) : null;
        if (
            $defaultKey !== null &&
            array_key_exists($defaultKey, $connections)
        ) {
            $this->default = $defaultKey;
        } else {
            $first = array_key_first($connections);
            if ($first === null) {
                throw new \RuntimeException('No database connection defined.');
            }

            $this->default = $first;
        }
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function load(): array
    {
        return $this->connections;
    }

    /**
     * @return string
     */
    public function defaultConnection(): string
    {
        return $this->default;
    }
}
