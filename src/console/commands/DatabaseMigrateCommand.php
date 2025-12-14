<?php declare(strict_types=1);

namespace src\console\commands;

use Phinx\Migration\Manager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use src\database\PhinxConfigFactory;

/**
 * @package src\console\commands
 */
final class DatabaseMigrateCommand extends Command
{
    protected static $defaultName = 'db:migrate';

    /**
     * @param PhinxConfigFactory $factory
     */
    public function __construct(private PhinxConfigFactory $factory)
    {
        parent::__construct(self::$defaultName);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this->setDescription('Runs migrations for the selected connection.')
            ->addOption(
                'connection',
                'c',
                InputOption::VALUE_REQUIRED,
                'Connection name',
                null,
            )
            ->addOption(
                'target',
                't',
                InputOption::VALUE_OPTIONAL,
                'Target migration version (timestamp)',
            )
            ->addOption(
                'seed',
                null,
                InputOption::VALUE_NONE,
                'Run seeders after applying migrations',
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
        $config = $this->factory->createConfig();
        $environment = strtolower(
            (string) ($input->getOption('connection') ??
                $config->getDefaultEnvironment()),
        );

        if (!$config->hasEnvironment($environment)) {
            $output->writeln(
                "<error>Environment '{$environment}' is not configured.</error>",
            );
            return Command::FAILURE;
        }

        $manager = new Manager($config, $input, $output);

        $target = $input->getOption('target');
        $version = $target === null ? null : (int) $target;

        $manager->migrate($environment, $version);
        $this->runSeed(
            $manager,
            $environment,
            $output,
            (bool) $input->getOption('seed'),
        );

        return Command::SUCCESS;
    }

    /**
     * @param Manager $manager
     * @param string $environment
     * @param OutputInterface $output
     * @param bool $shouldSeed
     * @return void
     */
    private function runSeed(
        Manager $manager,
        string $environment,
        OutputInterface $output,
        bool $shouldSeed,
    ): void {
        if (!$shouldSeed) {
            return;
        }

        $output->writeln(sprintf('<info>Seeding "%s"...</info>', $environment));

        $manager->seed($environment);
    }
}
