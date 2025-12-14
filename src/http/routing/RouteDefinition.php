<?php declare(strict_types=1);

namespace src\http\routing;

/**
 * @package src\http\routing
 */
final class RouteDefinition
{
    /**
     * @param array<int, string> $methods
     * @param array<int, string> $middleware
     */
    public function __construct(
        private array $methods,
        private string $path,
        private $handler,
        private array $middleware,
        private ?string $name = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function methods(): array
    {
        return $this->methods;
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return callable|string
     */
    public function handler(): callable|string
    {
        return $this->handler;
    }

    /**
     * @return array<int, string>
     */
    public function middleware(): array
    {
        return $this->middleware;
    }

    /**
     * @return string|null
     */
    public function name(): ?string
    {
        return $this->name;
    }
}
