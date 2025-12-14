<?php declare(strict_types=1);

namespace src\session;

/**
 * @package src\session
 */
final class SessionSettings
{
    /**
     * @param string $name
     * @param int $lifetime
     * @param string $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool $httpOnly
     * @param string $sameSite
     * @param string $scheme
     */
    /**
     * @param string $name
     * @param int $lifetime
     * @param string $path
     * @param string|null $domain
     * @param bool|null $secure
     * @param bool $httpOnly
     * @param string $sameSite
     * @param string $scheme
     */
    public function __construct(
        private string $name,
        private int $lifetime,
        private string $path,
        private ?string $domain,
        private ?bool $secure,
        private bool $httpOnly,
        private string $sameSite,
        private string $scheme,
    ) {}

    /**
     * @return string
     */
    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return int
     */
    /**
     * @return int
     */
    public function lifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * @return string
     */
    /**
     * @return string
     */
    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return string|null
     */
    /**
     * @return string|null
     */
    public function domain(): ?string
    {
        return $this->domain;
    }

    /**
     * @return bool
     */
    /**
     * @return bool
     */
    public function secure(): bool
    {
        if ($this->secure !== null) {
            return $this->secure;
        }

        return strtolower($this->scheme) === 'https';
    }

    /**
     * @return bool
     */
    /**
     * @return bool
     */
    public function httpOnly(): bool
    {
        return $this->httpOnly;
    }

    /**
     * @return string
     */
    /**
     * @return string
     */
    public function sameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function cookieParams(): array
    {
        $params = [
            'lifetime' => $this->lifetime(),
            'path' => $this->path(),
            'secure' => $this->secure(),
            'httponly' => $this->httpOnly(),
        ];

        $domain = $this->domain();
        if ($domain !== null && $domain !== '') {
            $params['domain'] = $domain;
        }

        $sameSite = $this->sameSite();
        if ($sameSite !== '') {
            $params['samesite'] = $sameSite;
        }

        return $params;
    }
}
