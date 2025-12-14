<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use src\container\ContainerInterface;
use src\queue\QueueManager;

if (!function_exists('queue')) {
    /**
     * @throws RuntimeException
     */
    function queue(): QueueManager
    {
        $container = env_container();

        if ($container === null) {
            throw new \RuntimeException(
                'Container not available for queue() helper.',
            );
        }

        if (!$container->has(QueueManager::class)) {
            throw new \RuntimeException('QueueManager is not registered.');
        }

        return $container->get(QueueManager::class);
    }
}
