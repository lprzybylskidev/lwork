<?php declare(strict_types=1);

namespace src\environment;

/**
 * @package src\environment
 */
final class Env
{
    /**
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    public function getString(string $key, ?string $default = null): ?string
    {
        $value = $this->getRaw($key);

        if ($value === null) {
            return $default;
        }

        return (string) $value;
    }

    /**
     * @param string $key
     * @param int|null $default
     * @return int|null
     */
    public function getInt(string $key, ?int $default = null): ?int
    {
        $value = $this->getRaw($key);

        if ($value === null) {
            return $default;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return $default;
        }

        if (!preg_match('/^-?\d+$/', $value)) {
            return $default;
        }

        return (int) $value;
    }

    /**
     * @param string $key
     * @param float|null $default
     * @return float|null
     */
    public function getFloat(string $key, ?float $default = null): ?float
    {
        $value = $this->getRaw($key);

        if ($value === null) {
            return $default;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return $default;
        }

        $value = str_replace(',', '.', $value);

        if (!is_numeric($value)) {
            return $default;
        }

        return (float) $value;
    }

    /**
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->getRaw($key);

        if ($value === null) {
            return $default;
        }

        $value = strtolower(trim((string) $value));

        if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        return $default;
    }

    /**
     * @param string $key
     * @param array<int, string> $allowed
     * @param string|null $default
     * @return string|null
     */
    public function getEnum(
        string $key,
        array $allowed,
        ?string $default = null,
    ): ?string {
        $value = $this->getString($key, null);

        if ($value === null) {
            return $default;
        }

        if (!in_array($value, $allowed, true)) {
            return $default;
        }

        return $value;
    }

    /**
     * @return string
     */
    public function appEnv(): string
    {
        return $this->getEnum(
            'APP_ENV',
            ['local', 'production'],
            'production',
        ) ?? 'production';
    }

    /**
     * @return bool
     */
    public function isLocal(): bool
    {
        return $this->appEnv() === 'local';
    }

    /**
     * @return bool
     */
    public function isProduction(): bool
    {
        return $this->appEnv() === 'production';
    }

    /**
     * @param string $key
     * @return string|null
     */
    private function getRaw(string $key): ?string
    {
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }

        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }

        $value = getenv($key);
        if ($value === false) {
            return null;
        }

        return $value;
    }

    /**
     * @return array<string, string>
     */
    public function all(): array
    {
        $values = [];

        foreach ($_ENV as $key => $value) {
            if (!is_string($key) || !is_scalar($value)) {
                continue;
            }

            $values[$key] = (string) $value;
        }

        foreach ($_SERVER as $key => $value) {
            if (
                !is_string($key) ||
                !is_scalar($value) ||
                array_key_exists($key, $values)
            ) {
                continue;
            }

            $values[$key] = (string) $value;
        }

        return $values;
    }
}
