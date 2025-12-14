<?php declare(strict_types=1);

namespace src\security;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @package src\security
 */
final class ThrottleMiddleware implements MiddlewareInterface
{
    /**
     * @param ThrottleService $service
     * @param SecurityConfig $config
     */
    public function __construct(
        private ThrottleService $service,
        private SecurityConfig $config,
    ) {}

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if (!$this->config->throttleEnabled()) {
            return $handler->handle($request);
        }

        $ip = $this->resolveClientIp($request);

        if (in_array($ip, $this->config->throttleWhitelist(), true)) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();
        [$limit, $window] = $this->resolveLimits($path);
        $key = $ip . '::' . $path;

        if (!$this->service->allow($key, $limit, $window)) {
            $retryAfter = $this->service->retryAfterSeconds($key, $window);

            $body = sprintf(
                'Too Many Requests. Retry after %d seconds.',
                $retryAfter,
            );

            return new Response(
                429,
                [
                    'Content-Type' => 'text/plain',
                    'Retry-After' => (string) $retryAfter,
                ],
                $body,
            );
        }

        return $handler->handle($request);
    }

    /**
     * @param string $path
     * @return array{int, int}
     */
    private function resolveLimits(string $path): array
    {
        $routes = $this->config->throttleRoutes();

        foreach ($routes as $route) {
            $match = $route['match'] ?? ($route['path'] ?? '');

            if ($match === '') {
                continue;
            }

            if (str_starts_with($path, $match)) {
                return [
                    (int) ($route['limit'] ??
                        $this->config->throttleGlobalLimit()),
                    (int) ($route['window'] ??
                        $this->config->throttleGlobalWindow()),
                ];
            }
        }

        return [
            $this->config->throttleGlobalLimit(),
            $this->config->throttleGlobalWindow(),
        ];
    }

    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    private function resolveClientIp(ServerRequestInterface $request): string
    {
        $server = $request->getServerParams();

        return $server['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
