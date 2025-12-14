<?php declare(strict_types=1);

namespace src\http\response;

use Nyholm\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * @package src\http\response
 */
final class Responses
{
    /**
     * @param array<mixed> $data
     * @param int $status
     * @return ResponseInterface
     */
    public static function json(
        array $data,
        int $status = 200,
    ): ResponseInterface {
        $payload = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        return new Response(
            $status,
            ['Content-Type' => 'application/json; charset=utf-8'],
            $payload ?? 'null',
        );
    }

    /**
     * @param string $body
     * @param int $status
     * @return ResponseInterface
     */
    public static function text(
        string $body,
        int $status = 200,
    ): ResponseInterface {
        return new Response(
            $status,
            ['Content-Type' => 'text/plain; charset=utf-8'],
            $body,
        );
    }

    /**
     * @param string $body
     * @param int $status
     * @return ResponseInterface
     */
    public static function html(
        string $body,
        int $status = 200,
    ): ResponseInterface {
        return new Response(
            $status,
            ['Content-Type' => 'text/html; charset=utf-8'],
            $body,
        );
    }
}
