<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use src\container\ContainerInterface;
use src\events\EventBus;

if (!function_exists('event')) {
    /**
     * @throws RuntimeException
     */
    function event(): EventBus
    {
        $container = env_container();

        if ($container === null) {
            throw new \RuntimeException(
                'Container not available for event() helper.',
            );
        }

        /** @var ContainerInterface $container */
        if (!$container->has(EventBus::class)) {
            throw new \RuntimeException('EventBus is not registered.');
        }

        return $container->get(EventBus::class);
    }
}
