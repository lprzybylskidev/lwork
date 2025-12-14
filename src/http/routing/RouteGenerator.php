<?php declare(strict_types=1);

namespace src\http\routing;

/**
 * @package src\http\routing
 */
final class RouteGenerator
{
    public function __construct(private Router $router) {}

    /**
     * @param string $name
     * @param array<string, mixed> $params
     * @return string
     */
    public function path(string $name, array $params = []): string
    {
        return $this->router->routePath($name, $params);
    }
}
