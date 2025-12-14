<?php declare(strict_types=1);

namespace src\flash;

use src\session\SessionManager;

/**
 * @package src\flash
 */
final class FlashBag
{
    private const SESSION_KEY_NEXT = 'flash.next';

    private array $current = [];
    private array $next = [];

    public function __construct(private SessionManager $session)
    {
        $this->current = $this->pull(self::SESSION_KEY_NEXT);
        $this->next = [];
    }

    /**
     * @param string $type
     * @param string $message
     */
    public function add(string $type, string $message): void
    {
        $this->next[$type][] = $message;
        $this->persistNext();
    }

    /**
     * @param string $type
     * @param string $message
     */
    public function now(string $type, string $message): void
    {
        $this->current[$type][] = $message;
    }

    /**
     * @param string $type
     * @return array<int, string>
     */
    public function get(string $type): array
    {
        $messages = $this->current[$type] ?? [];
        unset($this->current[$type]);
        return $messages;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function all(): array
    {
        $all = $this->current;
        $this->current = [];

        return $all;
    }

    /**
     * @param string $type
     * @return bool
     */
    public function has(string $type): bool
    {
        return isset($this->current[$type]) && $this->current[$type] !== [];
    }

    private function pull(string $key): array
    {
        $value = $this->session->has($key) ? $this->session->get($key, []) : [];

        $this->session->remove($key);

        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $type => $messages) {
            if (!is_array($messages)) {
                continue;
            }

            $result[$type] = array_map(
                static fn($item) => (string) $item,
                $messages,
            );
        }

        return $result;
    }

    /**
     * @return void
     */
    private function persistNext(): void
    {
        if ($this->next === []) {
            $this->session->remove(self::SESSION_KEY_NEXT);
            return;
        }

        $this->session->set(self::SESSION_KEY_NEXT, $this->next);
    }
}
