<?php declare(strict_types=1);

namespace app\providers;

use src\container\ContainerInterface;
use src\container\provider\ProviderInterface;
use src\http\routing\Router;

/**
 * @package app\providers
 */
final class AppHttpProvider implements ProviderInterface
{
    /**
     * @param ContainerInterface $container
     */
    public function register(ContainerInterface $container): void
    {
        $router = $container->get(Router::class);
        $basePath = rtrim((string) $container->param('basePath'), '/\\');

        $this->loadRoutes(
            $router,
            $basePath .
                DIRECTORY_SEPARATOR .
                'app' .
                DIRECTORY_SEPARATOR .
                'routes' .
                DIRECTORY_SEPARATOR .
                'web.php',
            $container,
        );

        $this->loadRoutes(
            $router,
            $basePath .
                DIRECTORY_SEPARATOR .
                'app' .
                DIRECTORY_SEPARATOR .
                'routes' .
                DIRECTORY_SEPARATOR .
                'api.php',
            $container,
        );

        if (!$router->hasPath('/error/{code}')) {
            $this->loadRoutes(
                $router,
                $basePath .
                    DIRECTORY_SEPARATOR .
                    'src' .
                    DIRECTORY_SEPARATOR .
                    'routes' .
                    DIRECTORY_SEPARATOR .
                    'error.php',
                $container,
            );
        }
    }

    /**
     * @param Router $router
     * @param string $path
     * @param ContainerInterface $container
     */
    private function loadRoutes(
        Router $router,
        string $path,
        ContainerInterface $container,
    ): void {
        if (!is_file($path)) {
            return;
        }

        $routes = require $path;

        if (!is_callable($routes)) {
            return;
        }

        $routes($router, $container);
    }
}
