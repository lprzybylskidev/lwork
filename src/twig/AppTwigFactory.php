<?php declare(strict_types=1);

namespace src\twig;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use src\container\ContainerInterface;
use src\twig\provider\TwigFilterProviderInterface;
use src\twig\provider\TwigFunctionProviderInterface;
use src\twig\provider\TwigGlobalProviderInterface;
use Twig\Environment;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;
use function abort;
use function flash_has;
use function flash_messages;
use function redirect;
use function request;
use function response;
use function route;
use function url;
use function asset;

/**
 * @package src\twig
 */
final class AppTwigFactory
{
    private const PROVIDER_DIRS = [
        'functions' => TwigFunctionProviderInterface::class,
        'filters' => TwigFilterProviderInterface::class,
        'globals' => TwigGlobalProviderInterface::class,
    ];

    /**
     * @param string $basePath
     * @param ContainerInterface $container
     */
    public function __construct(
        private string $basePath,
        private ContainerInterface $container,
    ) {}

    /**
     * @return Environment
     */
    public function create(): Environment
    {
        $loader = new FilesystemLoader($this->resolveViewsPath());
        $twig = new Environment($loader, [
            'autoescape' => false,
        ]);

        $this->registerProviderClasses($twig);
        $this->registerTagExtensions($twig);
        $this->registerHelperFunctions($twig);

        return $twig;
    }

    /**
     * @return string
     */
    private function resolveViewsPath(): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            'app' .
            DIRECTORY_SEPARATOR .
            'resources' .
            DIRECTORY_SEPARATOR .
            'views';
    }

    /**
     * @param Environment $twig
     * @return void
     */
    private function registerProviderClasses(Environment $twig): void
    {
        foreach (self::PROVIDER_DIRS as $dir => $interface) {
            foreach ($this->discoverClasses($dir) as $class) {
                if (!class_exists($class)) {
                    continue;
                }

                $provider = $this->container->get($class);

                if (!$provider instanceof $interface) {
                    continue;
                }

                if ($provider instanceof TwigFunctionProviderInterface) {
                    foreach ($provider->functions() as $function) {
                        $twig->addFunction($function);
                    }
                    continue;
                }

                if ($provider instanceof TwigFilterProviderInterface) {
                    foreach ($provider->filters() as $filter) {
                        $twig->addFilter($filter);
                    }
                    continue;
                }

                if ($provider instanceof TwigGlobalProviderInterface) {
                    foreach ($provider->globals() as $name => $value) {
                        $twig->addGlobal($name, $value);
                    }
                }
            }
        }
    }

    /**
     * @param Environment $twig
     * @return void
     */
    private function registerTagExtensions(Environment $twig): void
    {
        foreach ($this->discoverClasses('tags') as $class) {
            if (!class_exists($class)) {
                continue;
            }

            $extension = $this->container->get($class);

            if (!$extension instanceof ExtensionInterface) {
                continue;
            }

            $twig->addExtension($extension);
        }
    }

    private function registerHelperFunctions(Environment $twig): void
    {
        $twig->addFunction(
            new TwigFunction(
                'route',
                fn(
                    string $name,
                    array $params = [],
                    bool $absolute = false,
                ) => route($name, $params, $absolute),
            ),
        );

        $twig->addFunction(
            new TwigFunction(
                'url',
                fn(string $path = '/', bool $absolute = false) => url(
                    $path,
                    $absolute,
                ),
            ),
        );

        $twig->addFunction(new TwigFunction('request', fn() => request()));

        $twig->addFunction(new TwigFunction('response', fn() => response()));

        $twig->addFunction(
            new TwigFunction(
                'redirect',
                fn(string $target, int $status = 302) => redirect(
                    $target,
                    $status,
                ),
            ),
        );

        $twig->addFunction(
            new TwigFunction(
                'abort',
                fn(int $status, string $message = '') => abort(
                    $status,
                    $message,
                ),
            ),
        );

        $twig->addFunction(
            new TwigFunction('asset', fn(string $path) => asset($path)),
        );

        $twig->addFunction(
            new TwigFunction(
                'flash_messages',
                fn(?string $type = null) => flash_messages($type),
            ),
        );

        $twig->addFunction(
            new TwigFunction('has_flash', fn(string $type) => flash_has($type)),
        );
    }

    /**
     * @param string $subPath
     * @return array<int, string>
     */
    private function discoverClasses(string $subPath): array
    {
        $path = $this->resolveSubPath($subPath);

        if (!is_dir($path)) {
            return [];
        }

        $classes = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                \FilesystemIterator::SKIP_DOTS,
            ),
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($path) + 1);
            $relative = str_replace(['/', '\\'], '\\', $relative);
            $relative = substr($relative, 0, -4);

            $classes[] = sprintf('app\\twig\\%s\\%s', $subPath, $relative);
        }

        return $classes;
    }

    /**
     * @param string $subPath
     * @return string
     */
    private function resolveSubPath(string $subPath): string
    {
        return rtrim($this->basePath, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            'app' .
            DIRECTORY_SEPARATOR .
            'twig' .
            DIRECTORY_SEPARATOR .
            $subPath;
    }
}
