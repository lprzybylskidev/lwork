<?php declare(strict_types=1);

namespace src\console;

use src\container\ContainerInterface;
use Symfony\Component\Console\Command\Command;

/**
 * @package src\console
 */
final class CommandDiscovery
{
    private array $mapping = [
        'src/console/commands' => 'src\\console\\commands',
        'app/console/commands' => 'app\\console\\commands',
    ];

    /**
     * @param ContainerInterface $container
     * @param string $basePath
     */
    public function __construct(
        private ContainerInterface $container,
        private string $basePath,
    ) {}

    /**
     * @return array<int, Command>
     */
    public function discover(): array
    {
        $commands = [];

        foreach ($this->mapping as $relativeDir => $namespace) {
            $commands = array_merge(
                $commands,
                $this->loadFrom($relativeDir, $namespace),
            );
        }

        return $commands;
    }

    /**
     * @param string $relativeDir
     * @param string $namespace
     * @return array<int, Command>
     */
    private function loadFrom(string $relativeDir, string $namespace): array
    {
        $dir =
            $this->basePath .
            DIRECTORY_SEPARATOR .
            str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

        if (!is_dir($dir)) {
            return [];
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $dir,
                \FilesystemIterator::SKIP_DOTS,
            ),
        );

        $commands = [];

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $commandClass = $this->resolveClass(
                $file->getPathname(),
                $dir,
                $namespace,
            );

            if ($commandClass === null || !class_exists($commandClass)) {
                continue;
            }

            $command = $this->container->get($commandClass);

            if (!$command instanceof Command) {
                continue;
            }

            $commands[] = $command;
        }

        return $commands;
    }

    /**
     * @param string $filePath
     * @param string $rootDir
     * @param string $namespace
     * @return string|null
     */
    private function resolveClass(
        string $filePath,
        string $rootDir,
        string $namespace,
    ): ?string {
        if (!str_starts_with($filePath, $rootDir)) {
            return null;
        }

        $relative = ltrim(
            substr($filePath, strlen($rootDir)),
            DIRECTORY_SEPARATOR,
        );
        if ($relative === '' || !str_ends_with($relative, '.php')) {
            return null;
        }

        $relative = substr($relative, 0, -4);
        $relative = str_replace(['/', '\\'], '\\', $relative);

        return $namespace . '\\' . $relative;
    }
}
