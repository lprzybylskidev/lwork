<?php declare(strict_types=1);

namespace src\events\schedule;

use src\events\EventInterface;
use src\scheduler\TaskInterface;

/**
 * @package src\events\schedule
 */
abstract class TaskEvent implements EventInterface
{
    /**
     * @param TaskInterface $task
     */
    public function __construct(private TaskInterface $task) {}

    /**
     * @return TaskInterface
     */
    public function task(): TaskInterface
    {
        return $this->task;
    }
}
