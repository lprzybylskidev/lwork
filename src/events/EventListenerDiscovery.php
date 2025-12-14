<?php declare(strict_types=1);

namespace src\events;

/**
 * @package src\events
 */
final class EventListenerDiscovery
{
    /**
     * @param array<string, string> $mapping relativeDir => baseNamespace
     */
    public function __construct(
        private string $basePath,
        private array $mapping,
    ) {}

    /**
     * @return array<int, class-string<EventListenerInterface>>
     */
    public function discover(): array
    {
        $listeners = [];

        foreach ($this->mapping as $relativeDir => $namespace) {
            $dir =
                rtrim($this->basePath, DIRECTORY_SEPARATOR) .
                DIRECTORY_SEPARATOR .
                str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

            if (!is_dir($dir)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $dir,
                    \FilesystemIterator::SKIP_DOTS,
                ),
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $class = $this->resolveClass(
                    $file->getPathname(),
                    $dir,
                    $namespace,
                );
                if ($class === null || !class_exists($class)) {
                    continue;
                }

                if (!is_subclass_of($class, EventListenerInterface::class)) {
                    continue;
                }

                $listeners[] = $class;
            }
        }

        return $listeners;
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
