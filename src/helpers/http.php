<?php declare(strict_types=1);

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use src\http\exceptions\HttpException;
use src\http\routing\RouteGenerator;
use src\http\responder\Responder;

/**
 * @param ServerRequestInterface|null $request
 * @return ServerRequestInterface|null
 */
function http_current_request(
    ?ServerRequestInterface $request = null,
): ?ServerRequestInterface {
    static $current = null;

    if ($request !== null) {
        $current = $request;
    }

    return $current;
}

/**
 * @return ServerRequestInterface
 */
function request(): ServerRequestInterface
{
    $request = http_current_request();

    if ($request === null) {
        throw new RuntimeException('No current HTTP request is available.');
    }

    return $request;
}

/**
 * @return Responder
 */
function response(): Responder
{
    return responder();
}

/**
 * @param string $name
 * @param array<string, mixed> $params
 * @param bool $absolute
 * @return string
 */
function route(string $name, array $params = [], bool $absolute = false): string
{
    $container = env_container();

    if ($container === null) {
        throw new RuntimeException(
            'Container is not available for route() helper.',
        );
    }

    $generator = $container->get(RouteGenerator::class);

    $path = $generator->path($name, $params);

    if ($absolute) {
        return url($path, true);
    }

    return $path;
}

/**
 * @param string $path
 * @param bool $absolute
 * @return string
 */
function url(string $path = '/', bool $absolute = false): string
{
    $normalized = '/' . ltrim($path, '/');

    if (!$absolute) {
        return $normalized;
    }

    $req = request();
    $uri = $req->getUri()->withPath($normalized)->withQuery('');

    return (string) $uri;
}

/**
 * @param string $target
 * @param int $status
 * @return ResponseInterface
 */
function redirect(string $target, int $status = 302): ResponseInterface
{
    return (new Response($status))->withHeader('Location', $target);
}

/**
 * @param int $status
 * @return ResponseInterface
 */
function back(int $status = 302): ResponseInterface
{
    $referer = request()->getHeaderLine('Referer');

    if ($referer === '') {
        $referer = '/';
    }

    return redirect($referer, $status);
}

/**
 * @param string $path
 * @return string
 */
function asset(string $path): string
{
    $normalized = '/' . ltrim($path, '/');
    $assetBase = '/assets';

    $custom = env()->getString('ASSET_URL', '');
    $target = $assetBase . $normalized;

    if ($custom === '') {
        return $target;
    }

    return rtrim($custom, '/') . $target;
}

/**
 * @param int $status
 * @param string $message
 * @return never
 */
function abort(int $status, string $message = ''): never
{
    throw new HttpException($status, $message);
}
