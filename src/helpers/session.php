<?php declare(strict_types=1);

/**
 * @package src\helpers
 */

use src\container\ContainerInterface;
use src\session\SessionManager;

if (!function_exists('session_manager')) {
    /**
     * @throws RuntimeException
     */
    function session_manager(): SessionManager
    {
        $container = env_container();

        if ($container === null) {
            throw new RuntimeException(
                'Container not available for session_manager() helper.',
            );
        }

        if (!$container->has(SessionManager::class)) {
            throw new RuntimeException(
                'SessionManager is not registered in the container.',
            );
        }

        /** @var SessionManager $manager */
        $manager = $container->get(SessionManager::class);
        $manager->start();

        return $manager;
    }
}
