<?php declare(strict_types=1);

namespace src\security;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @package src\security
 */
final class SecurityHeadersMiddleware implements MiddlewareInterface
{
    /**
     * @param SecurityConfig $config
     */
    public function __construct(private SecurityConfig $config) {}

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        $response = $handler->handle($request);

        foreach ($this->config->headers() as $name => $value) {
            if ($value === null || $value === false) {
                continue;
            }

            if (
                strtolower($name) === 'strict-transport-security' &&
                strtolower($this->config->scheme()) !== 'https'
            ) {
                continue;
            }

            $response = $response->withHeader($name, (string) $value);
        }

        return $response;
    }
}
