<?php declare(strict_types=1);

namespace src\config;

/**
 * @package src\config
 */
final class ConfigManager
{
    private array $cache = [];

    /**
     * @param string $basePath
     */
    public function __construct(private string $basePath) {}

    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        [$file, $segment] = $this->parseKey($key);

        $data = $this->loadFile($file);

        if ($segment === null) {
            return $data === null ? $default : $data;
        }

        $value = $this->getValue($data ?? [], $segment);

        return $value === null ? $default : $value;
    }

    /**
     * @param string $key
     * @return array<int, string|null>
     */
    private function parseKey(string $key): array
    {
        $parts = explode('.', $key, 2);
        $file = $parts[0];
        $segment = $parts[1] ?? null;

        return [$file, $segment];
    }

    /**
     * @param string $file
     * @return array<string, mixed>|null
     */
    private function loadFile(string $file): ?array
    {
        if (array_key_exists($file, $this->cache)) {
            return $this->cache[$file];
        }

        $path =
            $this->basePath .
            DIRECTORY_SEPARATOR .
            'app' .
            DIRECTORY_SEPARATOR .
            'config' .
            DIRECTORY_SEPARATOR .
            "{$file}.php";

        if (!is_file($path)) {
            $this->cache[$file] = null;
            return null;
        }

        $data = include $path;
        if (!is_array($data)) {
            $this->cache[$file] = null;
            return null;
        }

        $this->cache[$file] = $data;
        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @param string $segment
     * @return mixed
     */
    private function getValue(array $data, string $segment): mixed
    {
        $parts = explode('.', $segment);

        foreach ($parts as $part) {
            if (!is_array($data) || !array_key_exists($part, $data)) {
                return null;
            }

            $data = $data[$part];
        }

        return $data;
    }
}
