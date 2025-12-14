<?php declare(strict_types=1);

namespace src\bootstrap\errors;

/**
 * @package src\bootstrap\errors
 */
final class ErrorCode
{
    /**
     * @return string
     */
    public static function generate(): string
    {
        return bin2hex(random_bytes(8));
    }
}
