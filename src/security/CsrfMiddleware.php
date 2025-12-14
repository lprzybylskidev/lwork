<?php declare(strict_types=1);

namespace src\security;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use src\http\exceptions\HttpException;

/**
 * @package src\security
 */
final class CsrfMiddleware implements MiddlewareInterface
{
    private const SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS', 'TRACE'];

    /**
     * @param CsrfService $csrfService
     */
    public function __construct(private CsrfService $csrfService) {}

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface {
        if ($this->isSafeMethod($request->getMethod())) {
            return $handler->handle($request);
        }

        $token = $this->resolveToken($request);
        if (
            !$this->csrfService->isTokenValid(
                $token,
                $this->resolveTokenId($request),
            )
        ) {
            throw new HttpException(419, 'Invalid CSRF token.');
        }

        return $handler->handle($request);
    }

    /**
     * @param string $method
     */
    /**
     * @param string $method
     * @return bool
     */
    private function isSafeMethod(string $method): bool
    {
        return in_array(strtoupper($method), self::SAFE_METHODS, true);
    }

    /**
     * @param ServerRequestInterface $request
     * @return string|null
     */
    /**
     * @param ServerRequestInterface $request
     * @return string|null
     */
    private function resolveToken(ServerRequestInterface $request): ?string
    {
        $body = $request->getParsedBody();

        if (is_array($body) && isset($body['_csrf_token'])) {
            return (string) $body['_csrf_token'];
        }

        $header = $request->getHeaderLine('X-CSRF-TOKEN');
        if ($header === '') {
            $header = $request->getHeaderLine('X-Csrf-Token');
        }

        return $header === '' ? null : $header;
    }

    /**
     * @param ServerRequestInterface $request
     */
    /**
     * @param ServerRequestInterface $request
     * @return string
     */
    private function resolveTokenId(ServerRequestInterface $request): string
    {
        $candidate = $request->getAttribute('csrf_token_id');

        if (is_string($candidate) && $candidate !== '') {
            return $candidate;
        }

        return $this->csrfService->defaultTokenId();
    }
}
