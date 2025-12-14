<?php declare(strict_types=1);

namespace src\console\commands;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use src\console\commands\FileGenerator;

/**
 * @package src\console\commands
 */
final class MakeFileCommand extends Command
{
    protected static $defaultName = 'make';

    /**
     * @param FileGenerator $generator
     */
    public function __construct(private FileGenerator $generator)
    {
        parent::__construct(self::$defaultName);
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $types = implode(', ', $this->generator->supportedTypes());
        $this->setDescription(
            'Generate scaffolding for controllers, events, migrations, etc.',
        )
            ->addArgument(
                'type',
                InputArgument::REQUIRED,
                'Type of resource to generate: ' . $types,
            )
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Class name or identifier.',
            )
            ->addOption(
                'context',
                'c',
                InputOption::VALUE_OPTIONAL,
                'Optional context/path inside the target base directory.',
                null,
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
        $type = (string) $input->getArgument('type');
        $name = (string) $input->getArgument('name');
        $context = (string) ($input->getOption('context') ?? '');

        try {
            $path = $this->generator->generate($type, $name, $context);
        } catch (RuntimeException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Created {$path}</info>");

        return Command::SUCCESS;
    }
}
