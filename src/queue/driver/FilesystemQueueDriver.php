<?php declare(strict_types=1);

namespace src\queue\driver;

use src\queue\QueueJob;

/**
 * @package src\queue\driver
 */
final class FilesystemQueueDriver implements QueueDriverInterface
{
    /**
     * @param string $directory
     */
    public function __construct(private string $directory) {}

    /**
     * @param QueueJob $job
     * @return void
     */
    public function push(QueueJob $job): void
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0777, true);
        }

        $payload = json_encode($job->toArray(), JSON_THROW_ON_ERROR);
        $path =
            $this->directory .
            DIRECTORY_SEPARATOR .
            uniqid('job_', true) .
            '.job';

        file_put_contents($path, $payload);
    }

    /**
     * @return QueueJob|null
     */
    public function pop(): ?QueueJob
    {
        $files = glob($this->directory . DIRECTORY_SEPARATOR . '*.job');

        if ($files === false || $files === []) {
            return null;
        }

        sort($files);

        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content === false) {
                @unlink($file);
                continue;
            }

            $data = json_decode($content, true);
            @unlink($file);

            if (!is_array($data)) {
                continue;
            }

            $job = QueueJob::fromArray($data);
            if ($job === null) {
                continue;
            }

            return $job;
        }

        return null;
    }
}
