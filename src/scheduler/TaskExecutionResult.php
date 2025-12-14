<?php declare(strict_types=1);

namespace src\scheduler;

/**
 * @package src\scheduler
 */
final class TaskExecutionResult
{
    /**
     * @param TaskInterface $task
     * @param int $exitCode
     * @param string $output
     * @param \Throwable|null $exception
     * @param bool $success
     */
    private function __construct(
        private TaskInterface $task,
        private int $exitCode,
        private string $output,
        private ?\Throwable $exception,
        private bool $success,
    ) {}

    /**
     * @param TaskInterface $task
     * @param int $exitCode
     * @param string $output
     * @return self
     */
    public static function success(
        TaskInterface $task,
        int $exitCode,
        string $output,
    ): self {
        return new self($task, $exitCode, $output, null, true);
    }

    /**
     * @param TaskInterface $task
     * @param \Throwable $exception
     * @param string $output
     * @param int $exitCode
     * @return self
     */
    public static function failure(
        TaskInterface $task,
        \Throwable $exception,
        string $output,
        int $exitCode = 1,
    ): self {
        return new self($task, $exitCode, $output, $exception, false);
    }

    /**
     * @return TaskInterface
     */
    public function task(): TaskInterface
    {
        return $this->task;
    }

    /**
     * @return int
     */
    public function exitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * @return string
     */
    public function output(): string
    {
        return $this->output;
    }

    /**
     * @return \Throwable|null
     */
    public function exception(): ?\Throwable
    {
        return $this->exception;
    }

    /**
     * @return bool
     */
    public function succeeded(): bool
    {
        return $this->success;
    }

    /**
     * @return bool
     */
    public function failed(): bool
    {
        return !$this->success;
    }
}
