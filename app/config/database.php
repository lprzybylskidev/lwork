<?php declare(strict_types=1);

/**
 * Assembles database connection metadata based on environment overrides.
 * It enumerates each connection listed in `DB_CONNECTIONS`, resolves per-connection drivers, and enforces a fallback default entry.
 * A combination of explicit `DB_*` keys and shared defaults guarantee every runtime knows how to establish a PDO handle.
 */
$basePath = dirname(__DIR__, 2);

if (!function_exists('resolveSqlitePath')) {
    /**
     * Normalizes the sqlite file path so database migrations and connections always use an absolute path.
     *
     * When the value is empty the builtin sqlite file under the project root is used.
     * Named files are resolved relative to `$basePath` unless they already describe an absolute path.
     *
     * @param string|null $value
     * @param string $basePath
     * @return string
     */
    function resolveSqlitePath(?string $value, string $basePath): string
    {
        $candidate = trim((string) ($value ?? ''));

        if ($candidate === '') {
            return $basePath . DIRECTORY_SEPARATOR . 'database.sqlite';
        }

        if (isAbsolutePath($candidate)) {
            return $candidate;
        }

        return $basePath . DIRECTORY_SEPARATOR . $candidate;
    }
}

if (!function_exists('isAbsolutePath')) {
    /**
     * Detects whether the provided path is already absolute across Windows and POSIX platforms.
     *
     * @param string $path
     * @return bool
     */
    function isAbsolutePath(string $path): bool
    {
        if ($path === '') {
            return false;
        }

        if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $path)) {
            return true;
        }

        return str_starts_with($path, DIRECTORY_SEPARATOR);
    }
}
$rawConnections = env()->getString('DB_CONNECTIONS', 'default') ?? 'default';
$names = array_filter(
    array_map('trim', explode(',', $rawConnections)),
    static fn(string $value) => $value !== '',
);

if ($names === []) {
    $names = ['default'];
}

$defaultTimezone = env()->getString('APP_TIMEZONE', 'UTC') ?? 'UTC';
$connections = [];

foreach ($names as $name) {
    $normalized = strtolower($name);
    $prefix = 'DB_' . strtoupper($name) . '_';

    $optionsJson = env()->getString($prefix . 'OPTIONS');
    $options = [];
    if ($optionsJson !== null && trim($optionsJson) !== '') {
        $decoded = json_decode($optionsJson, true);
        if (is_array($decoded)) {
            $options = $decoded;
        }
    }

    $driver = strtolower(
        env()->getString($prefix . 'DRIVER') ??
            env()->getString('DB_DRIVER', 'sqlite'),
    );

    $databaseValue = env()->getString($prefix . 'DATABASE');

    if ($driver === 'sqlite') {
        $databasePath = resolveSqlitePath($databaseValue, $basePath);
    } else {
        $databasePath = $databaseValue ?? '';
    }

    $connections[$normalized] = [
        'driver' => $driver,
        'dsn' => env()->getString($prefix . 'DSN'),
        'host' => env()->getString($prefix . 'HOST') ?? '127.0.0.1',
        'port' => env()->getInt($prefix . 'PORT') ?? 3306,
        'database' => $databasePath,
        'username' => env()->getString($prefix . 'USERNAME') ?? '',
        'password' => env()->getString($prefix . 'PASSWORD') ?? '',
        'charset' => env()->getString($prefix . 'CHARSET') ?? 'utf8mb4',
        'options' => $options,
        'timezone' =>
            env()->getString($prefix . 'TIMEZONE') ?? $defaultTimezone,
    ];
}

$explicitDefault = env()->getString('DB_DEFAULT_CONNECTION');

$defaultConnection = null;
if ($explicitDefault !== null) {
    $target = strtolower(trim($explicitDefault));
    if (
        $target !== '' &&
        in_array($target, array_map('strtolower', $names), true)
    ) {
        $defaultConnection = $target;
    }
}

if ($defaultConnection === null) {
    foreach ($names as $name) {
        $prefix = 'DB_' . strtoupper($name) . '_';
        if (env()->getBool($prefix . 'DEFAULT', false)) {
            $defaultConnection = strtolower($name);
            break;
        }
    }
}

if ($defaultConnection === null) {
    $defaultConnection = strtolower($names[0]);
}

/**
 * Provides the final normalized payload that the database layer will consume.
 * The `default` key references the active connection name, while `connections` contains every discovered driver descriptor.
 * Each connection descriptor includes driver, DSN fallback, host/port credentials, charset, options, and timezone metadata.
 *
 * @return array{
 *     default: string,
 *     connections: array<string, array{
 *         driver: string,
 *         dsn: string|null,
 *         host: string,
 *         port: int,
 *         database: string,
 *         username: string,
 *         password: string,
 *         charset: string,
 *         options: array<string, mixed>,
 *         timezone: string
 *     }>
 * }
 */
return [
    'default' => $defaultConnection,
    'connections' => $connections,
];
