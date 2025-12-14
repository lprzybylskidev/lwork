<?php declare(strict_types=1);

namespace src\http\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @package src\http\middleware
 */
final class Dispatcher implements RequestHandlerInterface
{
    private int $index = 0;
    private int $depth = 0;

    /**
     * @param array<int, object> $middlewares
     * @param RequestHandlerInterface $fallbackHandler
     */
    /**
     * @param array<int, object> $middlewares
     * @param RequestHandlerInterface $fallbackHandler
     */
    public function __construct(
        private array $middlewares,
        private RequestHandlerInterface $fallbackHandler,
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->depth === 0) {
            $this->index = 0;
        }

        $this->depth++;

        try {
            if (!isset($this->middlewares[$this->index])) {
                return $this->fallbackHandler->handle($request);
            }

            $middleware = $this->middlewares[$this->index++];

            return $middleware->process($request, $this);
        } finally {
            $this->depth--;

            if ($this->depth === 0) {
                $this->index = 0;
            }
        }
    }
}
