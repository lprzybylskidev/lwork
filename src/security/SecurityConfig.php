<?php declare(strict_types=1);

namespace src\security;

use function config;

/**
 * Holds security-related configuration values such as headers and throttle settings.
 *
 * @package src\security
 */
final class SecurityConfig
{
    /**
     * @param string $scheme
     * @param array<string, string|null> $headers
     * @param array<string, mixed> $throttle
     */
    public function __construct(
        private string $scheme,
        private array $headers,
        private array $throttle,
    ) {}

    /**
     * @return self
     */
    public static function fromConfig(): self
    {
        $scheme = config('security.scheme', 'http');
        $headers = config('security.headers', []);
        $throttle = config('security.throttle', []);

        return new self($scheme, $headers, $throttle);
    }

    /**
     * @return string
     */
    public function scheme(): string
    {
        return $this->scheme;
    }

    /**
     * @return array<string, string|null>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return bool
     */
    public function throttleEnabled(): bool
    {
        return $this->throttle['enabled'] ?? true;
    }

    /**
     * @return int
     */
    public function throttleGlobalLimit(): int
    {
        return (int) ($this->throttle['global']['limit'] ?? 60);
    }

    /**
     * @return int
     */
    public function throttleGlobalWindow(): int
    {
        return (int) ($this->throttle['global']['window'] ?? 60);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function throttleRoutes(): array
    {
        return $this->throttle['routes'] ?? [];
    }

    /**
     * @return array<int, string>
     */
    public function throttleWhitelist(): array
    {
        return $this->throttle['whitelist'] ?? [];
    }
}
