<?php declare(strict_types=1);

namespace src\http\exceptions;

/**
 * @package src\http\exceptions
 */
final class HttpException extends \RuntimeException
{
    /**
     * @param int $statusCode
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        private int $statusCode,
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return int
     */
    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
