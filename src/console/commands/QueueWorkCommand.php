<?php declare(strict_types=1);

namespace src\console\commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use src\queue\QueueManager;
use src\queue\QueueJob;

/**
 * @package src\console\commands
 */
final class QueueWorkCommand extends Command
{
    protected static $defaultName = 'queue:work';

    /**
     * @param QueueManager $queue
     */
    public function __construct(private QueueManager $queue)
    {
        parent::__construct(self::$defaultName);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Process jobs from the queue.')
            ->addOption(
                'limit',
                'l',
                InputOption::VALUE_OPTIONAL,
                'Maximum number of jobs to handle.',
            )
            ->addOption(
                'sleep',
                's',
                InputOption::VALUE_OPTIONAL,
                'Seconds to wait when queue is empty.',
                5,
            )
            ->addOption(
                'once',
                null,
                InputOption::VALUE_NONE,
                'Stop after processing a single job.',
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
        $limit = $input->getOption('limit');
        $limit = $limit === null ? null : (int) $limit;
        $sleep = (int) $input->getOption('sleep');
        $once = $input->getOption('once');

        $handled = 0;

        while (true) {
            $job = $this->queue->pop();

            if ($job === null) {
                if ($once || ($limit !== null && $handled >= $limit)) {
                    break;
                }

                if ($sleep > 0) {
                    sleep($sleep);
                }

                continue;
            }

            $this->queue->handle($job);
            $handled++;

            if ($output->isVerbose()) {
                $output->writeln(sprintf('Processed job %s', $job->class()));
            }

            if ($once || ($limit !== null && $handled >= $limit)) {
                break;
            }
        }

        return Command::SUCCESS;
    }
}
