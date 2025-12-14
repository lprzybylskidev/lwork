<?php declare(strict_types=1);

namespace src\queue;

/**
 * @package src\queue
 */
final class QueueJob
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private string $class,
        private array $payload = [],
    ) {}

    /**
     * @return string
     */
    public function class(): string
    {
        return $this->class;
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * @param array<string, mixed> $data
     */
    /**
     * @param array<string, mixed> $data
     * @return self|null
     */
    public static function fromArray(array $data): ?self
    {
        if (!isset($data['class']) || !is_string($data['class'])) {
            return null;
        }

        $payload = $data['payload'] ?? [];
        if (!is_array($payload)) {
            $payload = [];
        }

        return new self($data['class'], $payload);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'class' => $this->class,
            'payload' => $this->payload,
        ];
    }
}
