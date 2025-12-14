<?php declare(strict_types=1);

namespace src\security;

/**
 * @package src\security
 */
final class ThrottleService
{
    private array $retryAfter = [];

    /**
     * @param string $storageDir
     */
    public function __construct(private string $storageDir) {}

    /**
     * @param string $key
     * @param int $limit
     * @param int $window
     * @return bool
     */
    public function allow(string $key, int $limit, int $window): bool
    {
        $hash = $this->hash($key, $window);
        $path = $this->buildPath($hash);
        $now = time();

        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0777, true);
        }

        $data = ['calls' => 0, 'expires' => $now + $window];

        if (is_file($path)) {
            $payload = json_decode((string) file_get_contents($path), true);

            if (is_array($payload)) {
                $data = $payload;
            }
        }

        if ((int) ($data['expires'] ?? 0) < $now) {
            $data = ['calls' => 0, 'expires' => $now + $window];
        }

        $data['calls'] = ((int) ($data['calls'] ?? 0)) + 1;

        if ((int) ($data['calls'] ?? 0) > $limit) {
            $this->retryAfter[$hash] = max(
                0,
                (int) ($data['expires'] ?? $now + $window) - $now,
            );
            file_put_contents($path, json_encode($data));
            return false;
        }

        file_put_contents($path, json_encode($data));
        $this->retryAfter[$hash] = 0;

        return true;
    }

    /**
     * @param string $key
     * @param int $window
     * @return int
     */
    public function retryAfterSeconds(string $key, int $window): int
    {
        $hash = $this->hash($key, $window);

        return $this->retryAfter[$hash] ?? 0;
    }

    /**
     * @param string $key
     * @param int $window
     * @return string
     */
    private function hash(string $key, int $window): string
    {
        return sha1($key . '::' . $window);
    }

    /**
     * @param string $hash
     * @return string
     */
    private function buildPath(string $hash): string
    {
        return $this->storageDir . DIRECTORY_SEPARATOR . $hash . '.json';
    }
}
