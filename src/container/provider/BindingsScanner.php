<?php declare(strict_types=1);

namespace src\container\provider;

use src\container\ContainerInterface;

/**
 * @package src\container\provider
 */
final class BindingsScanner
{
    /**
     * @param array<string, string> $mapping relativeDir => baseNamespace
     */
    public function __construct(
        private string $basePath,
        private array $mapping,
    ) {}

    /**
     * @param ContainerInterface $container
     * @return void
     */
    public function register(ContainerInterface $container): void
    {
        foreach ($this->mapping as $relativeDir => $namespace) {
            $this->scanDirectory($container, $relativeDir, $namespace);
        }
    }

    /**
     * @param ContainerInterface $container
     * @param string $relativeDir
     * @param string $namespace
     * @return void
     */
    private function scanDirectory(
        ContainerInterface $container,
        string $relativeDir,
        string $namespace,
    ): void {
        $dir =
            $this->basePath .
            DIRECTORY_SEPARATOR .
            str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeDir);

        if (!is_dir($dir)) {
            return;
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

            $ref = new \ReflectionClass($class);

            if (!$ref->isInstantiable()) {
                continue;
            }

            $container->singleton($class);
        }
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
