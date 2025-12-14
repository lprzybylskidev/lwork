<?php declare(strict_types=1);

namespace src\http\emitter;

use Psr\Http\Message\ResponseInterface;

/**
 * @package src\http\emitter
 */
final class ResponseEmitter
{
    /**
     * @param ResponseInterface $response
     */
    public function emit(ResponseInterface $response): void
    {
        if (!headers_sent()) {
            http_response_code($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        $body = $response->getBody();

        if ($body->isSeekable()) {
            $body->rewind();
        }

        while (!$body->eof()) {
            echo $body->read(8192);
        }
    }
}
