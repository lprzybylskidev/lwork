<?php declare(strict_types=1);

namespace src\database;

use Phinx\Config\Config;
use Phinx\Config\ConfigInterface;

/**
 * @package src\database
 */
final class PhinxConfigFactory
{
    /**
     * @param DatabaseConfigLoader $loader
     * @param string $basePath
     * @param string $migrationsPath
     * @param string $seedersPath
     * @param array<string> $extraMigrations
     */
    public function __construct(
        private DatabaseConfigLoader $loader,
        private string $basePath,
        private string $migrationsPath,
        private string $seedersPath,
        private array $extraMigrations = [],
    ) {}

    /**
     * @return ConfigInterface
     */
    public function createConfig(): ConfigInterface
    {
        $connections = $this->loader->load();

        if ($connections === []) {
            throw new \RuntimeException(
                'No database connections configured for migrations.',
            );
        }

        $environments = [];

        foreach ($connections as $name => $config) {
            $environments[$name] = $this->buildEnvironment($config);
        }

        $defaultEnvironment = $this->loader->defaultConnection();

        if (!array_key_exists($defaultEnvironment, $environments)) {
            $defaultEnvironment = array_key_first($environments);
        }

        return new Config([
            'paths' => [
                'migrations' => $this->resolvePaths(
                    array_merge(
                        [$this->migrationsPath],
                        $this->extraMigrations,
                    ),
                ),
                'seeds' => $this->resolvePath($this->seedersPath),
            ],
            'environments' => array_merge(
                [
                    'default_migration_table' => 'phinxlog',
                    'default_environment' => $defaultEnvironment,
                ],
                $environments,
            ),
        ]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function buildEnvironment(array $config): array
    {
        $adapter = $this->mapAdapter($config['driver'] ?? '');

        $environment = [
            'adapter' => $adapter,
            'name' => $config['database'] ?? '',
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? null,
            'user' => $config['username'] ?? '',
            'pass' => $config['password'] ?? '',
            'charset' => $config['charset'] ?? null,
            'options' => $config['options'] ?? [],
        ];

        if (!empty($config['dsn'])) {
            $environment['dsn'] = $config['dsn'];
        }

        return array_filter($environment, static fn($value) => $value !== null);
    }

    /**
     * @param string $driver
     * @return string
     */
    private function mapAdapter(string $driver): string
    {
        return match (strtolower($driver)) {
            'pgsql', 'postgres', 'postgresql' => 'pgsql',
            'mssql', 'sqlsrv' => 'sqlsrv',
            default => 'mysql',
        };
    }

    /**
     * @param string $path
     * @return string
     */
    private function resolvePath(string $path): string
    {
        if (
            str_starts_with($path, DIRECTORY_SEPARATOR) ||
            preg_match('/^[A-Za-z]:\\\\/', $path)
        ) {
            return $path;
        }

        return rtrim($this->basePath, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param array<string> $paths
     * @return array<int, string>
     */
    private function resolvePaths(array $paths): array
    {
        return array_map(fn(string $path) => $this->resolvePath($path), $paths);
    }
}
