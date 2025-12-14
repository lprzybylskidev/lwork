<?php declare(strict_types=1);

namespace src\http\routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Http\Message\ServerRequestInterface;

use function FastRoute\simpleDispatcher;

/**
 * @package src\http\routing
 */
final class Router
{
    /**
     * @var array<int, RouteDefinition>
     */
    private array $routes = [];

    private array $groupStack = [];

    /**
     * @var array<string, array<int, string>>
     */
    private array $middlewareGroups = [];
    /**
     * @var array<string, RouteDefinition>
     */
    private array $namedRoutes = [];
    private ?Dispatcher $dispatcher = null;

    /**
     * @param string $name
     * @param array|string $middleware
     */
    public function middlewareGroup(
        string $name,
        array|string $middleware,
    ): void {
        $this->middlewareGroups[$name] = $this->normalizeMiddleware(
            $middleware,
        );
    }

    /**
     * @param string $path
     * @param callable|string $handler
     * @param array<int, mixed> $options
     */
    public function get(
        string $path,
        callable|string $handler,
        array $options = [],
    ): void {
        $this->addRoute(['GET', 'HEAD'], $path, $handler, $options);
    }

    /**
     * @param string $path
     * @param callable|string $handler
     * @param array<int, mixed> $options
     */
    public function post(
        string $path,
        callable|string $handler,
        array $options = [],
    ): void {
        $this->addRoute(['POST'], $path, $handler, $options);
    }

    /**
     * @param string $path
     * @param callable|string $handler
     * @param array<int, mixed> $options
     */
    public function put(
        string $path,
        callable|string $handler,
        array $options = [],
    ): void {
        $this->addRoute(['PUT'], $path, $handler, $options);
    }

    /**
     * @param string $path
     * @param callable|string $handler
     * @param array<int, mixed> $options
     */
    public function delete(
        string $path,
        callable|string $handler,
        array $options = [],
    ): void {
        $this->addRoute(['DELETE'], $path, $handler, $options);
    }

    /**
     * @param string $path
     * @param callable|string $handler
     * @param array<int, mixed> $options
     */
    public function any(
        string $path,
        callable|string $handler,
        array $options = [],
    ): void {
        $this->addRoute(
            ['GET', 'HEAD', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            $path,
            $handler,
            $options,
        );
    }

    /**
     * @param string $prefix
     * @param array|callable $optionsOrCallback
     * @param callable|null $callback
     */
    public function group(
        string $prefix,
        array|callable $optionsOrCallback,
        ?callable $callback = null,
    ): void {
        $attributes = [];
        if ($callback === null) {
            $callback = $optionsOrCallback;
        } else {
            $attributes = $optionsOrCallback;
        }

        $middleware = $this->normalizeMiddleware(
            $attributes['middleware'] ?? [],
        );

        $this->groupStack[] = [
            'prefix' => $this->normalizePrefix($prefix),
            'middleware' => $middleware,
        ];

        try {
            $callback($this);
        } finally {
            array_pop($this->groupStack);
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @return array<int, mixed>
     */
    public function dispatch(ServerRequestInterface $request): array
    {
        $dispatcher = $this->resolveDispatcher();
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        return $dispatcher->dispatch($method, $path);
    }

    /**
     * @param array<int, string> $methods
     * @param string $path
     * @param callable|string $handler
     * @param array<int, mixed> $options
     * @return void
     */
    private function addRoute(
        array $methods,
        string $path,
        callable|string $handler,
        array $options,
    ): void {
        $methods = array_map('strtoupper', $methods);
        $routePath = $this->buildRoutePath($path);
        $middleware = array_merge(
            $this->collectGroupMiddleware(),
            $this->normalizeMiddleware($options['middleware'] ?? []),
        );

        $name = $options['name'] ?? null;

        if ($name !== null && isset($this->namedRoutes[$name])) {
            throw new \RuntimeException(
                "Route name '{$name}' is already registered.",
            );
        }

        $route = new RouteDefinition(
            $methods,
            $routePath,
            $handler,
            $middleware,
            $name,
        );

        $this->routes[] = $route;

        if ($name !== null) {
            $this->namedRoutes[$name] = $route;
        }

        $this->dispatcher = null;
    }

    /**
     * @return Dispatcher
     */
    private function resolveDispatcher(): Dispatcher
    {
        if ($this->dispatcher !== null) {
            return $this->dispatcher;
        }

        $this->dispatcher = simpleDispatcher(function (
            RouteCollector $collector,
        ): void {
            foreach ($this->routes as $route) {
                $collector->addRoute($route->methods(), $route->path(), $route);
            }
        });

        return $this->dispatcher;
    }

    /**
     * @param array|string|null $middleware
     * @return array<int, string>
     */
    private function normalizeMiddleware(array|string|null $middleware): array
    {
        if ($middleware === null) {
            return [];
        }

        if (is_string($middleware)) {
            $middleware = [$middleware];
        }

        return $this->expandMiddleware(array_values($middleware));
    }

    /**
     * @param array<int, string> $middleware
     */
    /**
     * @param array<int, string> $middleware
     * @return array<int, string>
     */
    private function expandMiddleware(array $middleware): array
    {
        $expanded = [];

        foreach ($middleware as $item) {
            if (is_string($item) && isset($this->middlewareGroups[$item])) {
                $expanded = array_merge(
                    $expanded,
                    $this->middlewareGroups[$item],
                );
                continue;
            }

            $expanded[] = $item;
        }

        return $expanded;
    }

    /**
     * @param string $path
     * @return string
     */
    private function buildRoutePath(string $path): string
    {
        $normalized = $this->normalizePath($path);
        $prefix = $this->currentPrefix();

        if ($prefix === '') {
            return $normalized;
        }

        return rtrim($prefix, '/') . $normalized;
    }

    /**
     * @param string $path
     * @return string
     */
    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return '/';
        }

        return '/' . ltrim($path, '/');
    }

    /**
     * @param string $prefix
     * @return string
     */
    private function normalizePrefix(string $prefix): string
    {
        $prefix = trim($prefix);

        if ($prefix === '' || $prefix === '/') {
            return '';
        }

        return '/' . trim($prefix, '/');
    }

    /**
     * @return string
     */
    private function currentPrefix(): string
    {
        $prefixes = array_filter(
            array_map(fn(array $group) => $group['prefix'], $this->groupStack),
        );

        if ($prefixes === []) {
            return '';
        }

        return implode('', $prefixes);
    }

    /**
     * @return array<int, string>
     */
    private function collectGroupMiddleware(): array
    {
        $middlewares = [];

        foreach ($this->groupStack as $group) {
            $middlewares = array_merge($middlewares, $group['middleware']);
        }

        return $middlewares;
    }

    /**
     * @param string $name
     * @param array<string, mixed> $params
     * @return string
     */
    public function routePath(string $name, array $params = []): string
    {
        $route = $this->resolveNamedRoute($name);
        return $this->replaceParameters($route->path(), $params);
    }

    private function resolveNamedRoute(string $name): RouteDefinition
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \RuntimeException(
                "No route named '{$name}' is registered.",
            );
        }

        return $this->namedRoutes[$name];
    }

    /**
     * @param string $path
     * @param array<string, mixed> $params
     * @return string
     */
    private function replaceParameters(string $path, array $params): string
    {
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', (string) $value, $path);
        }

        return preg_replace('/\{[^}]+\}/', '', $path) ?? $path;
    }

    /**
     * @param string $path
     * @return bool
     */
    public function hasPath(string $path): bool
    {
        $normalized = $this->normalizePath($path);

        foreach ($this->routes as $route) {
            if ($route->path() === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, RouteDefinition>
     */
    public function definitions(): array
    {
        return $this->routes;
    }

    /**
     * @param int $status
     * @return RouteDefinition|null
     */
    public function findErrorRoute(int $status): ?RouteDefinition
    {
        $exact = "/error/{$status}";

        foreach ($this->routes as $route) {
            if ($route->path() === $exact) {
                return $route;
            }
        }

        $firstDefault = null;

        foreach ($this->routes as $route) {
            $path = $route->path();

            if (
                !str_starts_with($path, '/error/') ||
                !str_contains($path, '{') ||
                !str_contains($path, '}')
            ) {
                continue;
            }

            if ($route->name() !== 'error.default') {
                return $route;
            }

            $firstDefault ??= $route;
        }

        return $firstDefault;
    }
}
