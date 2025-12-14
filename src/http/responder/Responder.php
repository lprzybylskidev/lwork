<?php declare(strict_types=1);

namespace src\http\responder;

use Psr\Http\Message\ResponseInterface;
use src\http\response\Responses;

/**
 * @package src\http\responder
 */
final class Responder
{
    /**
     * @param mixed $data
     * @param int $status
     * @return ResponseInterface
     */
    public function json(mixed $data, int $status = 200): ResponseInterface
    {
        return Responses::json($data, $status);
    }

    /**
     * @param string $body
     * @param int $status
     * @return ResponseInterface
     */
    public function text(string $body, int $status = 200): ResponseInterface
    {
        return Responses::text($body, $status);
    }

    /**
     * @param string $body
     * @param int $status
     * @return ResponseInterface
     */
    public function html(string $body, int $status = 200): ResponseInterface
    {
        return Responses::html($body, $status);
    }

    /**
     * @param string $uri
     * @param int $status
     * @return ResponseInterface
     */
    public function redirect(string $uri, int $status = 302): ResponseInterface
    {
        return Responses::html('', $status)->withHeader('Location', $uri);
    }
}
