<?php declare(strict_types=1);

namespace src\session;

use SessionHandlerInterface;

/**
 * @package src\session
 */
final class SessionManager
{
    private ?SessionSettings $settings = null;
    private bool $configured = false;

    /**
     * @param SessionSettings $settings
     */
    /**
     * @param SessionSettings $settings
     * @return void
     */
    public function configure(SessionSettings $settings): void
    {
        $this->settings = $settings;

        if ($this->configured) {
            return;
        }

        session_name($settings->name());
        session_set_cookie_params($settings->cookieParams());
        $this->configured = true;
    }

    /**
     * @param SessionHandlerInterface $handler
     */
    /**
     * @param SessionHandlerInterface $handler
     * @return void
     */
    public function setHandler(SessionHandlerInterface $handler): void
    {
        session_set_save_handler($handler, true);
    }

    /**
     * @return void
     */
    /**
     * @return void
     */
    public function start(): void
    {
        if ($this->settings !== null && !$this->configured) {
            $this->configure($this->settings);
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * @param string $key
     * @param mixed $default
     */
    /**
     * @param string $key
     * @param mixed|null $default
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * @param string $key
     */
    /**
     * @param string $key
     * @return void
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * @return void
     */
    /**
     * @return void
     */
    public function clear(): void
    {
        $_SESSION = [];
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * @param bool $deleteOldSession
     */
    /**
     * @param bool $deleteOldSession
     * @return void
     */
    public function regenerate(bool $deleteOldSession = true): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        session_regenerate_id($deleteOldSession);
    }
}
