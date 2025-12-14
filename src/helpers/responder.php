<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use src\container\ContainerInterface;
use src\http\responder\Responder;

if (!function_exists('responder')) {
    /**
     * @throws RuntimeException
     */
    function responder(): Responder
    {
        $container = env_container();

        if ($container === null) {
            throw new \RuntimeException(
                'Container not available for responder() helper.',
            );
        }

        if (!$container->has(Responder::class)) {
            throw new \RuntimeException('Responder is not registered.');
        }

        return $container->get(Responder::class);
    }
}
