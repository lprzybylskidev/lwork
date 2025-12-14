<?php declare(strict_types=1);

namespace src\scheduler;

/**
 * @package src\scheduler
 */
final class TaskDiscovery
{
    /**
     * @param array<string, string> $mapping
     */
    public function __construct(
        private string $basePath,
        private array $mapping,
    ) {}

    /**
     * @return array<int, class-string<TaskInterface>>
     */
    public function discover(): array
    {
        $tasks = [];

        foreach ($this->mapping as $relative => $namespace) {
            $path =
                rtrim($this->basePath, DIRECTORY_SEPARATOR) .
                DIRECTORY_SEPARATOR .
                str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);

            if (!is_dir($path)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(
                    $path,
                    \FilesystemIterator::SKIP_DOTS,
                ),
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $relativePath = substr($file->getPathname(), strlen($path) + 1);
                $class = str_replace(
                    ['/', '\\'],
                    '\\',
                    substr($relativePath, 0, -4),
                );

                $tasks[] = $namespace . '\\' . $class;
            }
        }

        return $tasks;
    }
}
