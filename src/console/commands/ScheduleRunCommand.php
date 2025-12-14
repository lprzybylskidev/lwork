<?php declare(strict_types=1);

namespace src\console\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use src\scheduler\Scheduler;

/**
 * @package src\console\commands
 */
final class ScheduleRunCommand extends Command
{
    protected static $defaultName = 'schedule:run';

    /**
     * @param Scheduler $scheduler
     */
    public function __construct(private Scheduler $scheduler)
    {
        parent::__construct(self::$defaultName);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription(
            'Execute scheduled task classes from app/scheduler/tasks.',
        )->addOption(
            'task',
            't',
            InputOption::VALUE_OPTIONAL,
            'Run only the task with the given name.',
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output,
    ): int {
        $taskName = $input->getOption('task');
        $taskName = $taskName === null ? null : (string) $taskName;

        $results = $this->scheduler->run($taskName);

        if ($results === []) {
            $output->writeln('<comment>No scheduled tasks matched.</comment>');
            return Command::SUCCESS;
        }

        $failed = false;

        foreach ($results as $result) {
            $status = $result->succeeded() ? 'ok' : 'failed';
            $output->writeln(
                sprintf(
                    '%s [%s] (exit=%d)',
                    $result->task()->name(),
                    $status,
                    $result->exitCode(),
                ),
            );

            if ($result->failed()) {
                $failed = true;
            }
        }

        return $failed ? Command::FAILURE : Command::SUCCESS;
    }
}
