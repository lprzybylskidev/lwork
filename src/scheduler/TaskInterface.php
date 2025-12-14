<?php declare(strict_types=1);

namespace src\scheduler;

/**
 * @package src\scheduler
 */
interface TaskInterface
{
    /**
     * @return string
     */
    public function name(): string;

    /**
     * @param TaskRunner $runner
     * @return void
     */
    public function run(TaskRunner $runner): void;
}
