<?php declare(strict_types=1);

namespace src\scheduler;

use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Output\BufferedOutput;
use src\console\CommandDiscovery;
use src\container\ContainerInterface;
use src\events\EventBus;
use src\events\schedule\TaskFailedEvent;
use src\events\schedule\TaskFinishedEvent;
use src\events\schedule\TaskStartingEvent;
use src\scheduler\TaskExecutionResult;
use src\scheduler\TaskRunner;
use function config;

/**
 * @package src\scheduler
 */
final class Scheduler
{
    /**
     * @param ContainerInterface $container
     * @param TaskDiscovery $discovery
     * @param CommandDiscovery $commandDiscovery
     * @param EventBus $eventBus
     */
    public function __construct(
        private ContainerInterface $container,
        private TaskDiscovery $discovery,
        private CommandDiscovery $commandDiscovery,
        private EventBus $eventBus,
    ) {}

    /**
     * @param string|null $taskName optional name filter
     * @return array<int, TaskExecutionResult>
     */
    public function run(?string $taskName = null): array
    {
        $application = $this->buildApplication();
        $results = [];

        foreach ($this->discoverTasks() as $task) {
            if ($taskName !== null && $task->name() !== $taskName) {
                continue;
            }

            $results[] = $this->executeTask($application, $task);
        }

        return $results;
    }

    /**
     * @return array<int, TaskInterface>
     */
    private function discoverTasks(): array
    {
        $tasks = [];

        foreach ($this->discovery->discover() as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $instance = $this->container->get($class);

            if (!$instance instanceof TaskInterface) {
                continue;
            }

            $tasks[] = $instance;
        }

        return $tasks;
    }

    /**
     * @return ConsoleApplication
     */
    private function buildApplication(): ConsoleApplication
    {
        $application = new ConsoleApplication(
            config('app.name', 'lwork'),
            config('app.version', '0.1.0'),
        );
        $application->addCommands($this->commandDiscovery->discover());
        $application->setAutoExit(false);

        return $application;
    }

    /**
     * @param ConsoleApplication $application
     * @param TaskInterface $task
     * @return TaskExecutionResult
     */
    private function executeTask(
        ConsoleApplication $application,
        TaskInterface $task,
    ): TaskExecutionResult {
        $output = new BufferedOutput();
        $runner = new TaskRunner($application, $output);

        $this->eventBus->dispatch(new TaskStartingEvent($task));

        try {
            $task->run($runner);

            $result = TaskExecutionResult::success(
                $task,
                $runner->lastExitCode(),
                $runner->output(),
            );

            $this->eventBus->dispatch(new TaskFinishedEvent($result));

            return $result;
        } catch (\Throwable $exception) {
            $result = TaskExecutionResult::failure(
                $task,
                $exception,
                $runner->output(),
                $runner->lastExitCode(),
            );

            $this->eventBus->dispatch(new TaskFailedEvent($result));

            return $result;
        }
    }
}
