<?php declare(strict_types=1);

namespace src\events\schedule;

use src\scheduler\TaskExecutionResult;

/**
 * @package src\events\schedule
 */
final class TaskFinishedEvent extends TaskEvent
{
    /**
     * @var TaskExecutionResult
     */
    private TaskExecutionResult $result;

    /**
     * @param TaskExecutionResult $result
     */
    public function __construct(TaskExecutionResult $result)
    {
        parent::__construct($result->task());
        $this->result = $result;
    }

    /**
     * @return TaskExecutionResult
     */
    public function result(): TaskExecutionResult
    {
        return $this->result;
    }
}
