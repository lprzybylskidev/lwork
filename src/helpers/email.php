<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use src\mail\Mailer;

if (!function_exists('mailer')) {
    /**
     * @throws RuntimeException
     */
    function mailer(): Mailer
    {
        $container = env_container();

        if ($container === null) {
            throw new RuntimeException(
                'Container is not available for mailer().',
            );
        }

        if (!$container->has(Mailer::class)) {
            throw new RuntimeException(
                'Mailer is not registered in the container.',
            );
        }

        return $container->get(Mailer::class);
    }
}

if (!function_exists('send_mail')) {
    /**
     * @param string|array<int,string> $to
     * @param string $subject
     * @param string $body
     * @param array<string,string> $headers
     * @param bool $html
     * @return bool
     */
    function send_mail(
        string|array $to,
        string $subject,
        string $body,
        array $headers = [],
        bool $html = false,
    ): bool {
        return mailer()->send($to, $subject, $body, $headers, $html);
    }
}
