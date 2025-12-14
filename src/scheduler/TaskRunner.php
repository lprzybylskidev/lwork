<?php declare(strict_types=1);

namespace src\scheduler;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @package src\scheduler
 */
final class TaskRunner
{
    private ?int $lastExitCode = null;

    public function __construct(
        private ConsoleApplication $application,
        private BufferedOutput $output,
    ) {}

    /**
     * @param string $name
     * @param array<string, mixed> $arguments
     * @return int
     */
    public function runCommand(string $name, array $arguments = []): int
    {
        $input = new ArrayInput(array_merge(['command' => $name], $arguments));
        $input->setInteractive(false);

        $this->lastExitCode = $this->application->run($input, $this->output);
        return $this->lastExitCode;
    }

    /**
     * @param string $message
     * @param bool $newline
     * @return void
     */
    public function write(string $message, bool $newline = true): void
    {
        $this->output->write($message, $newline);
    }

    /**
     * @return string
     */
    public function output(): string
    {
        return $this->output->fetch();
    }

    /**
     * @return int
     */
    public function lastExitCode(): int
    {
        return $this->lastExitCode ?? 0;
    }
}
