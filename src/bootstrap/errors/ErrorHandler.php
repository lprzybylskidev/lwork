<?php declare(strict_types=1);

namespace src\bootstrap\errors;

/**
 * @package src\bootstrap\errors
 */
final class ErrorHandler
{
    /**
     * @return void
     */
    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
    }

    /**
     * @param int $severity
     * @param string $message
     * @param string $file
     * @param int $line
     * @return bool
     */
    public function handleError(
        int $severity,
        string $message,
        string $file,
        int $line,
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new \ErrorException($message, 0, $severity, $file, $line);
    }
}
