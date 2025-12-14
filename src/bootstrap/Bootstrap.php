<?php declare(strict_types=1);

namespace src\bootstrap;

use src\container\Container;
use app\providers\AppConsoleProvider;
use app\providers\AppHttpProvider;
use app\providers\AppProvider;
use function env_set_container;
use src\bootstrap\kernels\HttpKernel;
use src\container\ContainerInterface;
use src\bootstrap\kernels\ConsoleKernel;
use src\bootstrap\providers\HttpProvider;
use src\bootstrap\kernels\KernelInterface;
use src\container\provider\ProviderRunner;
use src\bootstrap\providers\CommonProvider;
use src\bootstrap\providers\ConsoleProvider;

/**
 * @package src\bootstrap
 */
final readonly class Bootstrap
{
    /**
     * @param string $basePath
     */
    public function __construct(private string $basePath) {}

    /**
     * @param RunMode $mode
     * @return int
     */
    public function init(RunMode $mode): int
    {
        $container = $this->buildContainer($mode);

        $kernel = match ($mode) {
            RunMode::Http => $container->get(HttpKernel::class),
            RunMode::Cli => $container->get(ConsoleKernel::class),
        };

        return $this->runKernel($kernel);
    }

    /**
     * @param RunMode $mode
     * @return ContainerInterface
     */
    public function buildContainer(RunMode $mode): ContainerInterface
    {
        $c = new Container();
        env_set_container($c);
        $c->instance(ContainerInterface::class, $c);

        $c->setParam('basePath', $this->basePath);
        $c->setParam('runMode', $mode->value);

        (new ProviderRunner([CommonProvider::class]))->run($c);

        match ($mode) {
            RunMode::Http => (new ProviderRunner([HttpProvider::class]))->run(
                $c,
            ),
            RunMode::Cli => (new ProviderRunner([ConsoleProvider::class]))->run(
                $c,
            ),
        };

        (new ProviderRunner([AppProvider::class]))->run($c);

        match ($mode) {
            RunMode::Http => (new ProviderRunner([
                AppHttpProvider::class,
            ]))->run($c),
            RunMode::Cli => (new ProviderRunner([
                AppConsoleProvider::class,
            ]))->run($c),
        };

        return $c;
    }

    /**
     * @param KernelInterface $kernel
     * @return int
     */
    private function runKernel(KernelInterface $kernel): int
    {
        try {
            return $kernel->handle();
        } catch (\Throwable $e) {
            return $kernel->handleException($e);
        }
    }
}
