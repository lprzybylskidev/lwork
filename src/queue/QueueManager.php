<?php declare(strict_types=1);

namespace src\queue;

use src\container\ContainerInterface;
use src\queue\driver\QueueDriverInterface;

/**
 * @package src\queue
 */
final class QueueManager
{
    /**
     * @param QueueDriverInterface $driver
     * @param ContainerInterface $container
     */
    public function __construct(
        private QueueDriverInterface $driver,
        private ContainerInterface $container,
    ) {}

    /**
     * @param class-string<JobInterface> $jobClass
     * @param array<string, mixed> $payload
     */
    public function push(string $jobClass, array $payload = []): void
    {
        $this->driver->push(new QueueJob($jobClass, $payload));
    }

    /**
     * @return QueueJob|null
     */
    public function pop(): ?QueueJob
    {
        return $this->driver->pop();
    }

    /**
     * @param QueueJob $job
     * @return void
     */
    public function handle(QueueJob $job): void
    {
        if (!class_exists($job->class())) {
            throw new \RuntimeException(
                sprintf('Queue job class %s not found.', $job->class()),
            );
        }

        $instance = $this->container->has($job->class())
            ? $this->container->get($job->class())
            : new $job->class();

        if (!$instance instanceof JobInterface) {
            throw new \RuntimeException(
                sprintf(
                    'Queue job %s must implement %s.',
                    $job->class(),
                    JobInterface::class,
                ),
            );
        }

        $this->container->call(
            [$instance, 'handle'],
            [
                'payload' => $job->payload(),
            ],
        );
    }
}
