<?php declare(strict_types=1);

namespace src\bootstrap\kernels;

use function config;
use function env_container;
use src\log\ErrorFileLogger;
use src\console\CommandDiscovery;
use src\bootstrap\errors\ErrorCode;
use src\bootstrap\errors\ErrorHandler;
use Symfony\Component\Console\Application as ConsoleApplication;

/**
 * @package src\bootstrap\kernels
 */
final class ConsoleKernel implements KernelInterface
{
    /**
     * @param CommandDiscovery $commandDiscovery
     */
    public function __construct(private CommandDiscovery $commandDiscovery) {}

    /**
     * @return int
     */
    public function handle(): int
    {
        $errors = new ErrorHandler([$this, 'handleException']);
        $errors->register();

        $application = new ConsoleApplication(
            config('app.name', 'lwork'),
            config('app.version', '0.1.0'),
        );
        $application->setCatchExceptions(false);

        $application->addCommands($this->commandDiscovery->discover());

        return $application->run();
    }

    /**
     * @param \Throwable $e
     * @return int
     */
    public function handleException(\Throwable $e): int
    {
        $errorCode = ErrorCode::generate();

        env_container()
            ?->get(ErrorFileLogger::class)
            ->log($e, $errorCode, [
                'mode' => 'cli',
            ]);

        $message = sprintf("Something went wrong (%s)\n", $errorCode);

        if (config('app.env', 'production') === 'local') {
            $message .= "\n";
            $message .= sprintf(
                "%s: %s in %s:%d\n",
                get_class($e),
                $e->getMessage(),
                $e->getFile(),
                $e->getLine(),
            );
            $message .= $e->getTraceAsString() . "\n";
        }

        fwrite(STDERR, $message);

        return 1;
    }
}
